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

        if (file_exists($path)) {
            throw new \InvalidArgumentException(
                'Project root must not contain a file or directory at path: "' . $path . '".'
            );
        }

        $success = mkdir($path);

        if (!$success) {
            throw new \RuntimeException('Failed to create directory at path: "' . $path . '".');
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
