<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractFile extends AbstractFileSystemElement
{
    public function create(string $location): void
    {
        $path = $location . '/' . $this->getName();

        $file = fopen($path, 'wb');

        if (false === $file){
            throw new \RuntimeException('Failed to create file at path: "' . $path . '".');
        }

        $written = fwrite($file, $this->getContents());

        if (false === $written){
            throw new \RuntimeException('Failed to write to file at path: "' . $path . '".');
        }

        $closed = fclose($file);

        if (false === $closed){
            throw new \RuntimeException('Failed to close file at path: "' . $path . '".');
        }

        $this->path = $path;
    }

    abstract protected function getName(): string;

    abstract protected function getContents(): string;
}
