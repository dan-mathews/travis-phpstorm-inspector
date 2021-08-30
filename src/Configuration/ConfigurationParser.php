<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Configuration;

use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

class ConfigurationParser
{
    private const KEY_IGNORED_SEVERITIES = 'ignored_severities';

    /**
     * @param string $path
     * @return Configuration
     * @throws ConfigurationException
     */
    public function parse(string $path): Configuration
    {
        $inspectionConfiguration = new Configuration();

        if (!file_exists($path)) {
            return $inspectionConfiguration;
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

        if (array_key_exists(self::KEY_IGNORED_SEVERITIES, $parsedConfiguration)) {
            if (!is_array($parsedConfiguration[self::KEY_IGNORED_SEVERITIES])) {
                throw new ConfigurationException('Ignored severities must be an array.');
            }

            $inspectionConfiguration->setIgnoredSeverities($parsedConfiguration[self::KEY_IGNORED_SEVERITIES]);
        }

        return $inspectionConfiguration;
    }
}
