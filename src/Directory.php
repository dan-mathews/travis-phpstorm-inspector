<?php

namespace TravisPhpstormInspector;

class Directory
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $realPath = realpath($path);

        if (
            false === $realPath ||
            false === is_dir($realPath) ||
            false === is_readable($realPath)
        ) {
            throw new \RuntimeException('Could not find a readable directory at ' . $path);
        }

        $this->path = $realPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}
