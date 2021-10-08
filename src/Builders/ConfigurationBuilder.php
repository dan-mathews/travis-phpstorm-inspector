<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Configuration\ConfigurationFile;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;

/**
 * @implements BuilderInterface<Configuration>
 */
class ConfigurationBuilder implements BuilderInterface
{
    public const FILENAME = 'travis-phpstorm-inspector.json';

    /**
     * @var ConfigurationFile
     */
    private $parsedConfigurationFile;

    /**
     * @var array<array-key, mixed>
     */
    private $options;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param array<array-key, mixed> $arguments
     * @param array<array-key, mixed> $options
     * @param string $appRootPath
     * @param string $workingDirectory
     * @param OutputInterface $output
     * @throws ConfigurationException
     * @throws FilesystemException
     */
    public function __construct(
        array $arguments,
        array $options,
        string $appRootPath,
        string $workingDirectory,
        OutputInterface $output
    ) {
        if (
            isset($arguments[InspectCommand::ARGUMENT_PROJECT_PATH]) &&
            !is_string($arguments[InspectCommand::ARGUMENT_PROJECT_PATH])
        ) {
            throw new ConfigurationException(InspectCommand::ARGUMENT_PROJECT_PATH . ' must be a string.');
        }

        // The project path can be specified in the command arguments or we assume it's the working directory.
        $projectPath = $arguments[InspectCommand::ARGUMENT_PROJECT_PATH] ?? $workingDirectory;

        $this->options = $options;

        $this->configuration = new Configuration($projectPath, $appRootPath, $output);

        // We set this first to allow control over verbosity ASAP.
        $this->setVerbose();

        $this->parsedConfigurationFile = new ConfigurationFile($projectPath . '/' . self::FILENAME, $output);
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->configuration;
    }

    /**
     * @throws ConfigurationException
     */
    public function build(): void
    {
        $this->parsedConfigurationFile->fill();
        $this->setIgnoreSeverities();
        $this->setIgnoreLines();
        $this->setDockerRepository();
        $this->setDockerTag();
        $this->setInspectionProfile();
        $this->setPhpVersion();
    }

    /**
     * @throws ConfigurationException
     */
    private function setIgnoreSeverities(): void
    {
        if (isset($this->options[InspectCommand::OPTION_IGNORE_SEVERITIES])) {
            if (!is_string($this->options[InspectCommand::OPTION_IGNORE_SEVERITIES])) {
                throw new ConfigurationException(
                    'The ' . InspectCommand::OPTION_IGNORE_SEVERITIES . ' command line option must be a string.'
                );
            }

            $this->configuration->setIgnoreSeverities(
                explode(',', $this->options[InspectCommand::OPTION_IGNORE_SEVERITIES])
            );

            return;
        }

        if (isset($this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_SEVERITIES])) {
            if (!is_array($this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_SEVERITIES])) {
                throw new ConfigurationException(
                    InspectCommand::OPTION_IGNORE_SEVERITIES . ' in the configuration file must be an array.'
                );
            }

            $this->configuration->setIgnoreSeverities(
                $this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_SEVERITIES]
            );
        }
    }

    /**
     * @throws ConfigurationException
     */
    private function setIgnoreLines(): void
    {


        if (isset($this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_LINES])) {
            if (!\is_array($this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_LINES])) {
                throw new ConfigurationException($errorMessage);
            }



            $this->configuration->setIgnoreLines(
                $this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_LINES]
            );
        }
    }

    /**
     * @throws ConfigurationException
     */
    private function setDockerRepository(): void
    {
        $value = $this->options[InspectCommand::OPTION_DOCKER_REPOSITORY]
            ?? $this->parsedConfigurationFile[InspectCommand::OPTION_DOCKER_REPOSITORY]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(InspectCommand::OPTION_DOCKER_REPOSITORY . ' must be a string.');
        }

        $this->configuration->setDockerRepository($value);
    }

    /**
     * @throws ConfigurationException
     */
    private function setDockerTag(): void
    {
        $value = $this->options[InspectCommand::OPTION_DOCKER_TAG]
            ?? $this->parsedConfigurationFile[InspectCommand::OPTION_DOCKER_TAG]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(InspectCommand::OPTION_DOCKER_TAG . ' must be a string.');
        }

        $this->configuration->setDockerTag($value);
    }

    private function setVerbose(): void
    {
        /** @var bool $verbose */
        $verbose = $this->options[InspectCommand::FLAG_VERBOSE];

        $this->configuration->setVerbose($verbose);
    }

    /**
     * @throws ConfigurationException
     */
    private function setInspectionProfile(): void
    {
        $value = $this->options[InspectCommand::OPTION_INSPECTION_PROFILE]
            ?? $this->parsedConfigurationFile[InspectCommand::OPTION_INSPECTION_PROFILE]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(InspectCommand::OPTION_INSPECTION_PROFILE . ' must be a string.');
        }

        $this->configuration->setInspectionProfilePath($value);
    }

    /**
     * @throws ConfigurationException
     */
    private function setPhpVersion(): void
    {
        $value = $this->options[InspectCommand::OPTION_PHP_VERSION]
            ?? $this->parsedConfigurationFile[InspectCommand::OPTION_PHP_VERSION]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(InspectCommand::OPTION_PHP_VERSION . ' must be a string.');
        }

        $this->configuration->setPhpVersion($value);
    }
}
