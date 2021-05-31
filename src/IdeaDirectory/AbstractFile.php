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
            echo 'Unable to open file ' . $this->getName();
            exit(1);
        }

        $written = fwrite($file, $this->getContents());

        if (false === $written){
            echo 'Unable to write file ' . $this->getName();
            exit(1);
        }

        $closed = fclose($file);

        if (false === $closed){
            echo 'Unable to close file ' . $this->getName();
            exit(1);
        }

        $this->path = $path;
    }

    abstract protected function getName(): string;

    abstract protected function getContents(): string;
}
