<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;

class ConfigurationBuilder
{
    public const FILENAME = 'travis-phpstorm-inspector.json';

    /**
     * @var ConfigurationFileArray
     */
    private $parsedConfigurationFile;

    /**
     * @var array<array-key, array<array-key, mixed>|null|scalar>
     */
    private $options;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param InputInterface $arguments
     * @param string $appRootPath
     * @param string $workingDirectory
     * @throws ConfigurationException
     * @throws InspectionsProfileException
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     */
    public function __construct(InputInterface $input, string $appRootPath, string $workingDirectory)
    {
        $projectPathArgument = $input->getArgument(InspectCommand::ARGUMENT_PROJECT_PATH);

        if ((null !== $projectPathArgument) && !is_string($projectPathArgument)) {
            throw new ConfigurationException(InspectCommand::ARGUMENT_PROJECT_PATH . ' must be a string.');
        }

        // The project path can be specified in the command arguments or we assume it's the working directory.
        $projectPath = $projectPathArgument ?? $workingDirectory;

        $this->options = $input->getOptions();

        $this->configuration = new Configuration($projectPath, $appRootPath);

        // We set this first to allow control over verbosity ASAP.
        $this->setVerbose();

        $this->parsedConfigurationFile = new ConfigurationFileArray($projectPath . '/' . self::FILENAME);
    }

    public function getConfiguration(): Configuration
    {
        return $this->configuration;
    }

    /**
     * @throws ConfigurationException
     * @throws InspectionsProfileException
     */
    public function build(): Configuration
    {
        $this->parsedConfigurationFile->fill();
        $this->setIgnoredSeverities();
        $this->setDockerRepository();
        $this->setDockerTag();
        $this->setInspectionProfile();

        return $this->configuration;
    }

    /**
     * @throws ConfigurationException
     */
    private function setIgnoredSeverities(): void
    {
        $ignoreSeveritiesArgument = $this->options[InspectCommand::OPTION_IGNORE_SEVERITIES]
            ?? $this->parsedConfigurationFile[InspectCommand::OPTION_IGNORE_SEVERITIES]
            ?? null;

        if (null === $ignoreSeveritiesArgument) {
            return;
        }

        if (!is_string($ignoreSeveritiesArgument)) {
            throw new ConfigurationException(InspectCommand::OPTION_IGNORE_SEVERITIES . ' must be a string.');
        }

        $this->configuration->setIgnoredSeverities(explode(',', $ignoreSeveritiesArgument));
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
     * @throws InspectionsProfileException
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
}
