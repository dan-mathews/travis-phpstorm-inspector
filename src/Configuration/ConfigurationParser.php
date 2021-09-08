<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Configuration;

use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

class ConfigurationParser
{
    private const KEY_IGNORED_SEVERITIES = 'ignored_severities';
    private const KEY_DOCKER_REPOSITORY = 'docker_repository';
    private const KEY_DOCKER_TAG = 'docker_tag';
    private const KEY_OVERWRITE_IDEA_DIR = 'overwrite_idea_dir';

    /**
     * @param string $path
     * @return Configuration
     * @throws ConfigurationException
     */
    public function parse(string $path): Configuration
    {
        if (!file_exists($path)) {
            throw new ConfigurationException('Could not find the configuration file at ' . $path);
        }

        $configurationContents = file_get_contents($path);

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

        $ignoredSeverities = $this->parseIgnoredSeverities($parsedConfiguration);
        $dockerRepository = $this->parseDockerRepository($parsedConfiguration);
        $dockerTag = $this->parseDockerTag($parsedConfiguration);
        $overwriteIdeaDir = $this->parseOverwriteIdeaDir($parsedConfiguration);

        return new Configuration(
            $ignoredSeverities,
            $dockerRepository,
            $dockerTag,
            $overwriteIdeaDir
        );
    }

    /**
     * @throws ConfigurationException
     */
    private function parseIgnoredSeverities(array $parsedConfiguration): array
    {
        if (!array_key_exists(self::KEY_IGNORED_SEVERITIES, $parsedConfiguration)) {
            return [];
        }

        if (!is_array($parsedConfiguration[self::KEY_IGNORED_SEVERITIES])) {
            throw new ConfigurationException(self::KEY_IGNORED_SEVERITIES . ' must be an array.');
        }

        return $parsedConfiguration[self::KEY_IGNORED_SEVERITIES];
    }

    /**
     * @throws ConfigurationException
     */
    private function parseDockerRepository(array $parsedConfiguration): ?string
    {
        if (!array_key_exists(self::KEY_DOCKER_REPOSITORY, $parsedConfiguration)) {
            return null;
        }

        if (!is_string($parsedConfiguration[self::KEY_DOCKER_REPOSITORY])) {
            throw new ConfigurationException(self::KEY_DOCKER_REPOSITORY . ' must be a string.');
        }

        return $parsedConfiguration[self::KEY_DOCKER_REPOSITORY];
    }

    /**
     * @throws ConfigurationException
     */
    private function parseDockerTag(array $parsedConfiguration): ?string
    {
        if (!array_key_exists(self::KEY_DOCKER_TAG, $parsedConfiguration)) {
            return null;
        }

        if (!is_string($parsedConfiguration[self::KEY_DOCKER_TAG])) {
            throw new ConfigurationException(self::KEY_DOCKER_TAG . ' must be a string.');
        }

        return $parsedConfiguration[self::KEY_DOCKER_TAG];
    }

    /**
     * @throws ConfigurationException
     */
    private function parseOverwriteIdeaDir(array $parsedConfiguration): bool
    {
        if (!array_key_exists(self::KEY_OVERWRITE_IDEA_DIR, $parsedConfiguration)) {
            return false;
        }

        if (!is_bool($parsedConfiguration[self::KEY_OVERWRITE_IDEA_DIR])) {
            throw new ConfigurationException(self::KEY_OVERWRITE_IDEA_DIR . ' must be a boolean.');
        }

        return $parsedConfiguration[self::KEY_OVERWRITE_IDEA_DIR];
    }
}
