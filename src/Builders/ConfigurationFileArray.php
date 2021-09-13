<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Exceptions\ConfigurationException;

class ConfigurationFileArray implements \ArrayAccess
{
    /**
     * @var array
     */
    private $data;

    /**
     * @throws ConfigurationException
     */
    public function __construct(string $configurationPath)
    {
        $this->data = $this->getParsedConfigurationFile($configurationPath);
    }

    /**
     * @throws ConfigurationException
     */
    private function getParsedConfigurationFile($configurationPath): array
    {
        if (!file_exists($configurationPath)) {
            echo 'Could not find the configuration file at ' . $configurationPath . ', assuming that command line '
                . 'arguments or defaults are being used.';

            return [];
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

        $invalidKeys = array_diff(array_keys($parsedConfiguration), ConfigurationBuilder::KEYS);

        if ([] !== $invalidKeys) {
            throw new ConfigurationException(
                'Configuration file contains invalid keys: "' . implode('", "', $invalidKeys) . '"'
            );
        }

        return $parsedConfiguration;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}