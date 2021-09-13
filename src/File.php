<?php

namespace TravisPhpstormInspector;

class File
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
            false === is_file($realPath) ||
            false === is_readable($realPath)
        ) {
            throw new \RuntimeException('Could not find a readable file at ' . $path);
        }

        $this->path = $realPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }
}