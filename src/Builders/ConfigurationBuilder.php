<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;

class ConfigurationBuilder
{
    public const FILENAME = 'travis-phpstorm-inspector.json';
    private const PROJECT_PATH = 'projectPath';

    private const KEY_INSPECTION_PROFILE = 'inspectionProfile';
    private const KEY_IGNORED_SEVERITIES = 'ignored_severities';
    private const KEY_DOCKER_REPOSITORY = 'docker_repository';
    private const KEY_DOCKER_TAG = 'docker_tag';
    private const KEY_VERBOSE = 'verbose';


    public const KEYS = [
        self::KEY_IGNORED_SEVERITIES,
        self::KEY_DOCKER_REPOSITORY,
        self::KEY_DOCKER_TAG,
        self::KEY_VERBOSE,
        self::KEY_INSPECTION_PROFILE
    ];

    /**
     * @var ConfigurationFileArray
     */
    private $parsedConfigurationFile;

    /**
     * @var string[]
     */
    private $arguments;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param string[] $arguments
     * @param string $appRootPath
     * @param string $workingDirectory
     * @throws ConfigurationException
     * @throws InspectionsProfileException
     * @throws \RuntimeException
     */
    public function __construct(array $arguments, string $appRootPath, string $workingDirectory)
    {
        //project path can be specified in the commandline arguments or we assume it's the working directory
        $projectPath = $arguments[1] ?? $workingDirectory;

        $this->arguments = $this->extractArgumentKeyValues($arguments);

        $this->configuration = new Configuration($projectPath, $appRootPath);

        // set first to allow control over verbosity asap
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
        if (
            !isset($this->arguments[self::KEY_IGNORED_SEVERITIES]) &&
            !isset($this->parsedConfigurationFile[self::KEY_IGNORED_SEVERITIES])
        ) {
            return;
        }

        try {
            if (isset($this->arguments[self::KEY_IGNORED_SEVERITIES])) {
                $value = json_decode($this->arguments[self::KEY_IGNORED_SEVERITIES], true, 512, JSON_THROW_ON_ERROR);
            } else {
                /** @psalm-var mixed $value */
                $value = $this->parsedConfigurationFile[self::KEY_IGNORED_SEVERITIES];
            }
        } catch (\JsonException $e) {
            throw new ConfigurationException(
                self::KEY_IGNORED_SEVERITIES . ' could not be json decoded from the command arguments.',
                1,
                $e
            );
        }

        if (!is_array($value)) {
            throw new ConfigurationException(
                self::KEY_IGNORED_SEVERITIES . ' must be an array.'
            );
        }

        $this->configuration->setIgnoredSeverities($value);
    }

    /**
     * @throws ConfigurationException
     */
    private function setDockerRepository(): void
    {
        $value = $this->arguments[self::KEY_DOCKER_REPOSITORY]
            ?? $this->parsedConfigurationFile[self::KEY_DOCKER_REPOSITORY]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(self::KEY_DOCKER_REPOSITORY . ' must be a string.');
        }

        $this->configuration->setDockerRepository($value);
    }

    /**
     * @throws ConfigurationException
     */
    private function setDockerTag(): void
    {
        $value = $this->arguments[self::KEY_DOCKER_TAG]
            ?? $this->parsedConfigurationFile[self::KEY_DOCKER_TAG]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(self::KEY_DOCKER_TAG . ' must be a string.');
        }

        $this->configuration->setDockerTag($value);
    }

    /**
     * @param string[] $arguments
     * @return array<string, string>
     * @throws ConfigurationException
     */
    private function extractArgumentKeyValues(array $arguments): array
    {
        $output = [];

        $argvCount = count($arguments);

        for ($i = 2; $i < $argvCount; $i++) {
            $equalsPosition = strpos($arguments[$i], '=');

            if (false === $equalsPosition) {
                throw new ConfigurationException(
                    'Argument ' . $arguments[$i] . ' does not contain an = character'
                );
            }

            $key = substr($arguments[$i], 0, $equalsPosition);

            if (!in_array($key, self::KEYS, true)) {
                throw new ConfigurationException(' is not a valid argument');
            }

            $value = substr($arguments[$i], $equalsPosition + 1);

            if (!is_string($value)) {
                throw new ConfigurationException(
                    'Could not divide this argument into key and value parts: ' . $arguments[$i]
                );
            }

            $output[$key] = $value;
        }

        return $output;
    }

    /**
     * @throws ConfigurationException
     */
    private function setVerbose(): void
    {
        if (!isset($this->arguments[self::KEY_VERBOSE])) {
            $this->configuration->setVerbose(false);
            return;
        }

        switch ($this->arguments[self::KEY_VERBOSE]) {
            case 'true':
                $this->configuration->setVerbose(true);
                return;
            case 'false':
                $this->configuration->setVerbose(false);
                return;
            default:
                throw new ConfigurationException(self::KEY_VERBOSE . ' must be true or false.');
        }
    }

    /**
     * @throws ConfigurationException
     * @throws InspectionsProfileException
     */
    private function setInspectionProfile(): void
    {
        $value = $this->arguments[self::KEY_INSPECTION_PROFILE]
            ?? $this->parsedConfigurationFile[self::KEY_INSPECTION_PROFILE]
            ?? null;

        if (null === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(self::KEY_INSPECTION_PROFILE . ' must be a string.');
        }

        $this->configuration->setInspectionProfile($value);
    }
}
