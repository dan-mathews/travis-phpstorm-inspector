<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Process\Process;
use TravisPhpstormInspector\Exceptions\DockerException;

class DockerFacade
{
    /**
     * @var array
     */
    private const OPTIONS_TYPE = [
        'bind',
        'volume',
        'tmpfs'
    ];

    /**
     * @var array
     */
    private const OPTIONS_BIND_PROPAGATION = [
        'rprivate',
        'private',
        'rshared',
        'shared',
        'rslave',
        'slave'
    ];

    /**
     * @var string
     */
    private $imageName;

    /**
     * @var string
     */
    private $repository;

    /**
     * @var string
     */
    private $tag;

    /**
     * @var string
     */
    private $imageDigest;

    /**
     * @var string[]
     */
    private $mounts = [];

    /**
     * @var string[]
     */
    private $commands = [];

    /**
     * @throws DockerException
     */
    public function __construct(string $repository, string $tag)
    {
        $this->repository = $repository;

        $this->tag = $tag;

        $this->imageName = $repository . ':' . $tag;

        try {
            $process = new Process(['docker', 'image', 'inspect', $this->imageName]);

            $process->run();
        } catch (\Throwable $e) {
            throw new DockerException('Could not create and run docker processes', 1, $e);
        }

        if (!$process->isSuccessful()) {
            throw new DockerException('Docker image \'' . $this->imageName . '\' doesn\'t seem to exist locally.');
        }

        try {
            $output = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

            $this->imageDigest = $output[0]['ContainerConfig']['Image'] ?? null;
        } catch (\Throwable $e) {
            throw new DockerException('Docker image digest could not be established from docker command.');
        }
    }

    /**
     * @throws DockerException
     */
    public function mount(
        string $source,
        string $target,
        bool $readonly = false,
        string $bindPropagation = 'private',
        string $type = 'bind'
    ): self {
        if (!in_array($type, self::OPTIONS_TYPE, true)) {
            throw new DockerException('Docker mount type must be one of: ' . implode(', ', self::OPTIONS_TYPE));
        }

        if (!in_array($bindPropagation, self::OPTIONS_BIND_PROPAGATION, true)) {
            throw new DockerException(
                'Docker mount bind propagation must be one of: ' . implode(', ', self::OPTIONS_BIND_PROPAGATION)
            );
        }

        $this->mounts[] = '--mount';

        $this->mounts[] = 'type=' . $type
            . ',source=' . $source
            . ',target=' . $target
            . ',bind-propagation=' . $bindPropagation
            . ($readonly ? ',readonly' : '');

        return $this;
    }

    public function addCommand(string $command): self
    {
        $this->commands[] = $command;

        return $this;
    }

    /**
     * @throws DockerException
     */
    public function run(): void
    {
        $bashWrapperCommand = '"' . implode('; ', $this->commands) . '"';

        $command = 'docker run ' . implode(' ', $this->mounts) . ' ' . $this->imageName . ' /bin/bash -c '
            . $bashWrapperCommand;

        $output = [];

        $code = 1;

        exec($command . ' 2>&1', $output, $code);

        if ($code !== 0) {
            throw new DockerException(
                "Failure when running docker command.\nOutput was: \n\t" . implode("\n\t", $output)
            );
        }
    }

    public function getRepository(): string
    {
        return $this->repository;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function getImageName(): string
    {
        return $this->imageName;
    }

    public function getImageDigest(): string
    {
        return $this->imageDigest;
    }
}
