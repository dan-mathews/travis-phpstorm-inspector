<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;

class DockerImage
{
    /**
     * @var string
     */
    private $reference;

    /**
     * @throws ConfigurationException
     */
    public function __construct(Configuration $configuration, bool $verbose)
    {
        $this->reference = $configuration->getDockerRepository() . ':' . $configuration->getDockerTag();

        $code = 1;

        $output = [];

        $verboseOutput = $verbose ? '' : ' 2>&1';

        try {
            exec('docker pull ' . $this->reference . $verboseOutput, $output, $code);
        } catch (\Throwable $e) {
            throw new ConfigurationException('Could not pull docker image ' . $this->reference, 1, $e);
        }

        if ($code !== 0) {
            throw new ConfigurationException('Could not pull docker image ' . $this->reference);
        }
    }

    public function getReference(): string
    {
        return $this->reference;
    }
}