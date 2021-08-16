<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractCreatableDirectory extends AbstractCreatableFileSystemElement
{
    /**
     * @var AbstractCreatableFile[]
     */
    protected $files = [];

    /**
     * @var AbstractCreatableDirectory[]
     */
    protected $directories = [];

    /**
     * @param string $location
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function create(string $location): void
    {
        parent::create($location);

        if (file_exists($this->getPath())) {
            throw new \InvalidArgumentException(
                'Project must not contain a file or directory at path: "' . $this->getPath() . '".'
            );
        }

        if (!mkdir($this->getPath()) && !is_dir($this->getPath())) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->getPath()));
        }

        foreach ($this->directories as $directory) {
            $directory->create($this->getPath());
        }

        foreach ($this->files as $file) {
            $file->create($this->getPath());
        }
    }
}
