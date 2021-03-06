<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Configuration\ConfigurationFile;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;

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
     * @param string $workingDirectory
     * @param Filesystem $filesystem
     * @param OutputInterface $output
     * @throws ConfigurationException
     * @throws FilesystemException
     */
    public function __construct(
        array $arguments,
        array $options,
        string $workingDirectory,
        Filesystem $filesystem,
        OutputInterface $output
    ) {
        if (
            isset($arguments[InspectCommand::ARGUMENT_PROJECT_PATH]) &&
            !is_string($arguments[InspectCommand::ARGUMENT_PROJECT_PATH])
        ) {
            throw new ConfigurationException(InspectCommand::ARGUMENT_PROJECT_PATH . ' must be a string.');
        }

        // The project path can be specified in the command arguments, or we assume it's the working directory.
        $projectPath = $arguments[InspectCommand::ARGUMENT_PROJECT_PATH] ?? $workingDirectory;

        $this->options = $options;

        $this->configuration = new Configuration($projectPath, $filesystem, $output);

        $configurationPath = $projectPath . '/' . self::FILENAME;

        if (isset($this->options[InspectCommand::OPTION_CONFIGURATION])) {
            /** @var string $configurationPath */
            $configurationPath = $this->options[InspectCommand::OPTION_CONFIGURATION];

            if (
                !is_file($configurationPath) ||
                !is_readable($configurationPath)
            ) {
                throw new ConfigurationException(
                    'Could not read the configuration file at ' . $configurationPath
                );
            }
        }

        $this->parsedConfigurationFile = new ConfigurationFile($configurationPath, $output);
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
        $this->setExcludeFolders();
        $this->setDockerRepository();
        $this->setDockerTag();
        $this->setInspectionProfile();
        $this->setPhpVersion();
        $this->setWholeProject();
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
        if (!isset($this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_LINES])) {
            return;
        }

        if (!\is_array($this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_LINES])) {
            throw new ConfigurationException(InspectCommand::OPTION_IGNORE_LINES . ' must be an array.');
        }

        $this->configuration->setIgnoreLines(
            $this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_LINES]
        );
    }

    /**
     * @throws ConfigurationException
     * @psalm-suppress MixedArgumentTypeCoercion - we validate it's an array<string>
     */
    private function setExcludeFolders(): void
    {
        if (!isset($this->parsedConfigurationFile[InspectCommand::OPTION_EXCLUDE_FOLDERS])) {
            return;
        }

        $excludeFoldersConfiguration = $this->parsedConfigurationFile[InspectCommand::OPTION_EXCLUDE_FOLDERS];

        if (!\is_array($excludeFoldersConfiguration)) {
            throw new ConfigurationException(InspectCommand::OPTION_EXCLUDE_FOLDERS . ' must be an array.');
        }

        foreach ($excludeFoldersConfiguration as $folderName) {
            if (!\is_string($folderName)) {
                throw new ConfigurationException(
                    InspectCommand::OPTION_EXCLUDE_FOLDERS . ' must be an array of strings.'
                );
            }
        }

        $this->configuration->setExcludeFolders($excludeFoldersConfiguration);
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

        $this->configuration->setInspectionProfile($value);
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

    private function setWholeProject(): void
    {
        if (!isset($this->options[InspectCommand::OPTION_WHOLE_PROJECT])) {
            return;
        }

        /** @var bool $wholeProject */
        $wholeProject = $this->options[InspectCommand::OPTION_WHOLE_PROJECT];

        $this->configuration->setWholeProject($wholeProject);
    }
}
