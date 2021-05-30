<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractFile
{
    public function create(string $directoryPath): void
    {
        $file = fopen($directoryPath . '/' . $this->getName(), 'w') or die('Unable to open file ' . $this->getName());
        fwrite($file, $this->getContents());
        fclose($file);
    }

    abstract protected function getContents(): string;

    abstract protected function getName(): string;
}
