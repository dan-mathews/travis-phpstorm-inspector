<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\App;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

class ConfigurationBuilder
{
    public const FILENAME = 'travis-phpstorm-inspector.json';

    private const KEY_PROJECT_PATH = 'projectPath';
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
     * @throws ConfigurationException
     * @throws \LogicException
     */
    public function __construct(array $arguments, string $appRootPath, string $workingDirectory)
    {
        $this->arguments = $this->extractArguments($arguments);

        //project path can be specified in the commandline arguments or we assume it's the working directory
        $projectPath = $this->arguments[self::KEY_PROJECT_PATH] ?? $workingDirectory;

        if (!is_string($projectPath)) {
            throw new ConfigurationException(
                'Could not establish project path as string from argument 1 or current working directory.'
            );
        }

        $this->configuration = new Configuration($projectPath, $appRootPath);

        // built first to allow control over verbosity asap
        $this->setVerbose();

        $this->parsedConfigurationFile = new ConfigurationFileArray($projectPath . '/' . self::FILENAME);
    }

    /**
     * @throws ConfigurationException
     */
    public function build(): Configuration
    {
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
                $value = json_decode($this->arguments[self::KEY_IGNORED_SEVERITIES], false, 512, JSON_THROW_ON_ERROR);
            } else {
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
     * @throws ConfigurationException
     */
    private function extractArguments(array $arguments): array
    {
        $output = [];

        $output[self::KEY_PROJECT_PATH] = $arguments[1] ?? null;

        $output[self::KEY_INSPECTION_PROFILE] = $arguments[2] ?? null;

        $argvCount = count($arguments);

        for ($i = 3; $i < $argvCount; $i++) {
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
            return;
        }

        switch ($this->arguments[self::KEY_VERBOSE]) {
            case 'true':
                $this->configuration->setVerbose(true);
                break;
            case 'false':
                $this->configuration->setVerbose(false);
                break;
            default:
                throw new ConfigurationException(self::KEY_VERBOSE . ' must be true or false.');
        }
    }

    /**
     * @throws ConfigurationException
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
