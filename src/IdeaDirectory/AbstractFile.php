<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractFile
{
    public function create(string $directoryPath): void
    {
        $file = fopen($directoryPath . '/' . $this->getName(), 'w');

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
    }

    abstract protected function getContents(): string;

    abstract public function getName(): string;
}
