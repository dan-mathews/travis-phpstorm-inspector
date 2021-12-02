<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Configuration;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

/** @implements \ArrayAccess<string, mixed> */
class ConfigurationFile implements \ArrayAccess
{
    /**
     * @var array<string, mixed>
     */
    private $data = [];

    /**
     * @var string
     */
    private $path;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(string $configurationPath, OutputInterface $output)
    {
        $this->path = $configurationPath;
        $this->output = $output;
    }

    /**
     * @throws ConfigurationException
     */
    public function fill(): void
    {
        $this->data = $this->getParsedConfigurationFile($this->path);
    }

    /**
     * @param string $configurationPath
     * @return array<string, mixed>
     * @throws ConfigurationException
     */
    private function getParsedConfigurationFile(string $configurationPath): array
    {
        if (!file_exists($configurationPath)) {
            $this->output->writeln(
                'Could not find a configuration file at ' . $configurationPath . ', assuming that command line '
                . 'arguments or defaults are being used'
            );

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

        $invalidKeys = array_diff(array_keys($parsedConfiguration), InspectCommand::OPTIONS);

        if ([] !== $invalidKeys) {
            throw new ConfigurationException(
                'Configuration file contains invalid keys: "' . implode('", "', $invalidKeys) . '"'
            );
        }

        /** @psalm-var array<string, mixed> $parsedConfiguration */
        return $parsedConfiguration;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /** @psalm-suppress MixedReturnStatement these \ArrayAccess methods can't be strictly typed */
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
