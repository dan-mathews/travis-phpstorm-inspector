<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractDirectory extends AbstractFileSystemElement
{
    /**
     * @var AbstractFile[]
     */
    protected $files = [];

    /**
     * @var AbstractDirectory[]
     */
    protected $directories = [];

    public function create(string $location): void
    {
        $path = $location . '/' . $this->getName();

        $output = [];

        $code = 0;

        exec('mkdir ' . $path, $output, $code);

        if (0 !== $code) {
            echo 'Failed to create directory ' . $this->getName();
            exit(1);
        }

        foreach ($this->directories as $directory) {
            $directory->create($path);
        }

        foreach ($this->files as $file) {
            $file->create($path);
        }

        $this->path = $path;
    }

    abstract protected function getName(): string;
}
