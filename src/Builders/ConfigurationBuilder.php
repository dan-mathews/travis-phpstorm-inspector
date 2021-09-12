<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\App;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

class ConfigurationBuilder
{
    public const FILENAME = 'travis-phpstorm-inspector.json';

    private const KEY_IGNORED_SEVERITIES = 'ignored_severities';
    private const KEY_DOCKER_REPOSITORY = 'docker_repository';
    private const KEY_DOCKER_TAG = 'docker_tag';
    private const KEY_VERBOSE = 'verbose';
    private const KEY_INSPECTION_PROFILE = 'inspectionProfile';

    private const KEYS = [
        self::KEY_IGNORED_SEVERITIES,
        self::KEY_DOCKER_REPOSITORY,
        self::KEY_DOCKER_TAG,
        self::KEY_VERBOSE,
        self::KEY_INSPECTION_PROFILE
    ];

    /**
     * @var string
     */
    private $projectPath;

    /**
     * @var array<mixed>|null
     */
    private $parsedConfigurationFile;

    /**
     * @var mixed
     */
    private $arguments;

    /**
     * @var string
     */
    private $appRootPath;

    /**
     * @throws ConfigurationException
     * @throws \LogicException
     */
    public function __construct(array $arguments, string $appRootPath, string $workingDirectory)
    {
        $this->arguments = $this->extractArguments($arguments);

        //project path can be specified in the commandline arguments or we assume it's the working directory
        $projectPath = $this->arguments['projectPath'] ?? $workingDirectory;

        if (!is_string($projectPath)) {
            throw new ConfigurationException(
                'Could not establish project path as string from argument 1 or current working directory.'
            );
        }

        $this->projectPath = $projectPath;

        $appRootPath = realpath($appRootPath);

        if (false === $appRootPath) {
            throw new \LogicException(
                'Could not establish the path to ' . App::NAME . ' app.'
            );
        }

        $this->appRootPath = $appRootPath;
    }

    /**
     * @throws ConfigurationException
     */
    public function getParsedConfigurationFile(): array
    {
        //TODO make own class
        if (null !== $this->parsedConfigurationFile) {
            return $this->parsedConfigurationFile;
        }

        $configurationPath = $this->projectPath . '/' . self::FILENAME;

        if (!file_exists($configurationPath)) {
            throw new ConfigurationException('Could not find the configuration file at ' . $configurationPath);
        }

        $configurationContents = file_get_contents($configurationPath);

        if (false === $configurationContents) {
            throw new ConfigurationException('Could not read the configuration file.');
        }

        try {
            $parsedConfiguration = json_decode($configurationContents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ConfigurationException(
                'Could not process the configuration file as json.',
                1,
                $e
            );
        }

        if (!is_array($parsedConfiguration)) {
            throw new ConfigurationException('Configuration should be written as a json object.');
        }

        $invalidKeys = array_diff(array_keys($parsedConfiguration), self::KEYS);

        if ([] !== $invalidKeys) {
            throw new ConfigurationException(
                'Configuration file contains invalid keys: "' . implode('", "', $invalidKeys) . '"'
            );
        }

        $this->parsedConfigurationFile = $parsedConfiguration;

        return $parsedConfiguration;
    }

    /**
     * @return Configuration
     * @throws ConfigurationException
     */
    public function build(): Configuration
    {
        $ignoredSeverities = $this->parseIgnoredSeverities();
        $dockerRepository = $this->parseDockerRepository();
        $dockerTag = $this->parseDockerTag();
        $verbose = $this->parseVerbose();
        $inspectionProfile = $this->parseInspectionProfile();

        return new Configuration(
            $ignoredSeverities,
            $dockerRepository,
            $dockerTag,
            $this->appRootPath,
            $verbose,
            $this->projectPath,
            $inspectionProfile
        );
    }

    /**
     * @return array<mixed>
     * @throws ConfigurationException
     */
    private function parseIgnoredSeverities(): ?array
    {
        $value = $this->getValueFromArgsOrConfig(self::KEY_IGNORED_SEVERITIES);

        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $value = json_decode($value, false, 512, JSON_THROW_ON_ERROR);
        }

        if (!is_array($value)) {
            throw new ConfigurationException(
                self::KEY_IGNORED_SEVERITIES . ' must be an array or json encoded string which can parsed as an array.'
            );
        }

        return $value;
    }

    /**
     * @return string|null
     * @throws ConfigurationException
     */
    private function parseDockerRepository(): ?string
    {
        $value = $this->getValueFromArgsOrConfig(self::KEY_DOCKER_REPOSITORY);

        if (
            null !== $value &&
            !is_string($value)
        ) {
            throw new ConfigurationException(self::KEY_DOCKER_REPOSITORY . ' must be a string.');
        }

        return $value;
    }

    /**
     * @return string|null
     * @throws ConfigurationException
     */
    private function parseDockerTag(): ?string
    {
        $value = $this->getValueFromArgsOrConfig(self::KEY_DOCKER_TAG);

        if (
            null !== $value &&
            !is_string($value)
        ) {
            throw new ConfigurationException(self::KEY_DOCKER_TAG . ' must be a string.');
        }

        return $value;
    }

    /**
     * @throws ConfigurationException
     */
    private function extractArguments(array $arguments): array
    {
        $output = [];

        $output['projectPath'] = $arguments[1] ?? null;

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
    private function getValueFromArgsOrConfig(string $key)
    {
        return $this->arguments[$key] ?? $this->getParsedConfigurationFile()[$key] ?? null;
    }

    private function parseVerbose(): ?bool
    {
        $value = $this->getValueFromArgsOrConfig(self::KEY_VERBOSE);

        if (null === $value) {
            return null;
        }

        if (!is_bool((bool) $value)) {
            throw new ConfigurationException(self::KEY_VERBOSE . ' must be a boolean (true or false).');
        }

        return (bool) $value;
    }

    private function parseInspectionProfile(): ?string
    {
        $value = $this->getValueFromArgsOrConfig(self::KEY_INSPECTION_PROFILE);

        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new ConfigurationException(self::KEY_INSPECTION_PROFILE . ' must be a string.');
        }

        return $value;
    }
}
