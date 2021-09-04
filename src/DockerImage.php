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
    public function __construct(string $dockerRepository, string $dockerTag)
    {
        $this->reference = $dockerRepository . ':' . $dockerTag;

        try {
            passthru('docker pull ' . $this->reference);
        } catch (\Throwable $e) {
            throw new ConfigurationException('Could not pull docker image ' . $this->reference, 1, $e);
        }
    }

    public function getReference(): string
    {
        return $this->reference;
    }
}