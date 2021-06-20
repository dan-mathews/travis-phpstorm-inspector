<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class Project
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $rootPath)
    {
        $this->path = $this->validatePath($rootPath);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function validatePath(string $path): string
    {
        $fullPath = realpath($path);

        if (false === $fullPath) {
            throw new \InvalidArgumentException(
                'The given project root (' . $path . ') cannot be opened, or does not exist.'
            );
        }

        if (!is_dir($fullPath)) {
            throw new \InvalidArgumentException('The resolved project root (' . $fullPath . ') is not a directory.');
        }

        return $fullPath;
    }
}
