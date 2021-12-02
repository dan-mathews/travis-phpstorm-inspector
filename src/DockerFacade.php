<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\DockerException;

class DockerFacade
{
    /**
     * @var array<int, string>
     */
    private const OPTIONS_TYPE = [
        'bind',
        'volume',
        'tmpfs'
    ];

    /**
     * @var array<int, string>
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
     * @var string[]
     */
    private $mounts = [];

    /**
     * @var string[]
     */
    private $commands = [];

    /**
     * @var CommandRunner
     */
    private $commandRunner;

    /**
     * @throws DockerException
     */
    public function __construct(string $repository, string $tag, CommandRunner $commandRunner)
    {
        $this->repository = $repository;

        $this->tag = $tag;

        $this->imageName = $repository . ':' . $tag;

        $this->commandRunner = $commandRunner;

        try {
            $command = 'docker image inspect ' . $this->imageName;

            $commandRunner->run($command);
        } catch (\RuntimeException $e) {
            throw new DockerException(
                'Docker image \'' . $this->imageName . '\' doesn\'t seem to exist locally.',
                1,
                $e
            );
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
        if ([] === $this->commands) {
            throw new DockerException('Could not run docker commands as no commands were added');
        }

        $bashWrapperCommand = '"' . implode('; ', $this->commands) . '"';

        $command = 'docker run ' . implode(' ', $this->mounts) . ' ' . $this->imageName . ' /bin/bash -c '
            . $bashWrapperCommand;

        try {
            $this->commandRunner->run($command);
        } catch (\RuntimeException $e) {
            throw new DockerException('Could not successfully perform Docker run command', 1, $e);
        }

        $this->commands = [];

        $this->mounts = [];
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
}
