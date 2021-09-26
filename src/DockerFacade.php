<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Process\Process;
use TravisPhpstormInspector\Exceptions\DockerException;

class DockerFacade
{
    private const OPTIONS_TYPE = ['bind', 'volume', 'tmpfs'];
    private const OPTIONS_BIND_PROPAGATION = ['rprivate', 'private', 'rshared', 'shared', 'rslave', 'slave'];
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
     * @var int|null
     */
    private $timeout;

    /**
     * @throws DockerException
     */
    public function __construct(string $repository, string $tag)
    {
        $this->repository = $repository;

        $this->tag = $tag;

        $this->imageName = $repository . ':' . $tag;

        $process = new Process(['docker', 'image', 'inspect', $this->imageName]);

        $process->run();

        if (!$process->isSuccessful()) {
            throw new DockerException('Docker image \'' . $this->imageName . '\' doesn\'t seem to exist locally.');
        }

        try {
            $output = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

            $this->imageDigest = $output[0]['ContainerConfig']['Image'] ?? null;
        } catch (\JsonException $e) {
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
            . ($readonly) ? ',readonly' : '';

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
    public function run(): int
    {
//        $commands = [];
//
//        $count = count($this->commands);
//
//        for ($i = 0; $i < $count; $i++) {
//            $commands[] = $this->commands[$i];
//
//            if ($i < $count - 1) {
//                $commands[] = ';';
//            }
//        }

        $bashWrapperCommand = '"' . implode('; ', $this->commands) . '"';

        $command = array_merge(
            ['docker', 'run'],
            $this->mounts,
            [$this->imageName],
            [$this->commands[0]]
//            ['/bin/bash'],
//            ['-c'],
//            [$bashWrapperCommand]
        );

        $commandAsString = implode(' ', $command);

        echo 'Running command: ' . $commandAsString;
        echo 'Running command: ' . var_export($command, true);

        $process = new Process($command, null, null, null, $this->timeout);

        // add callable to print to output if verbose
        $process->run();

        echo $process->getErrorOutput();
        echo $process->getOutput();

        if (!$process->isSuccessful()) {
            throw new DockerException('Docker run command was not successful: ' . $commandAsString);
        }

        return $process->getExitCode();
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
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
