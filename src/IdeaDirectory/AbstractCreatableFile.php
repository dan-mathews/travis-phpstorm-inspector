<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractCreatableFile extends AbstractCreatableFileSystemElement
{
    /**
     * @param string $location
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function create(string $location): void
    {
        parent::create($location);

        $file = fopen($this->getPath(), 'wb');

        if (false === $file) {
            throw new \RuntimeException('Failed to create file at path: "' . $this->getPath() . '".');
        }

        $written = fwrite($file, $this->getContents());

        if (false === $written) {
            throw new \RuntimeException('Failed to write to file at path: "' . $this->getPath() . '".');
        }

        $closed = fclose($file);

        if (false === $closed) {
            throw new \RuntimeException('Failed to close file at path: "' . $this->getPath() . '".');
        }
    }

    abstract protected function getContents(): string;
}
