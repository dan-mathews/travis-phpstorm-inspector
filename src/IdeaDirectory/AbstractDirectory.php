<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractDirectory
{
    /**
     * @var AbstractDirectory[]
     */
    protected $directories = [];

    /**
     * @var AbstractFile[]
     */
    protected $files = [];

    /**
     * @var string
     */
    protected $path = '';

    public function create(string $projectRoot): void
    {
        $this->path = $projectRoot . '/' . $this->getName();

        $output = [];

        $code = 0;

        exec('mkdir ' . $this->path, $output, $code);

        if (0 !== $code) {
            echo 'Failed to create directory ' . $this->getName();
            exit(1);
        }

        foreach ($this->getDirectories() as $directory) {
            $directory->create($this->path);
        }

        foreach ($this->getFiles() as $file) {
            $file->create($this->path);
        }
    }

    public function addDirectory(AbstractDirectory $directory): void
    {
        $this->directories[] = $directory;
    }

    public function addFile(AbstractFile $file): void
    {
        $this->files[] = $file;
    }

    /**
     * @return AbstractDirectory[]
     */
    protected function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @return AbstractFile[]
     */
    protected function getFiles(): array
    {
        return $this->files;
    }

    abstract protected function getName(): string;
}
