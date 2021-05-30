<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractDirectory
{
    public function create(string $projectRoot): void
    {
        $path = $projectRoot . '/' . $this->getName();

        exec('mkdir ' . $path);

        /* @var AbstractFile $fileClass */
        foreach ($this->getFiles() as $fileClass) {
            $file = new $fileClass();

            $file->create($path);
        }
    }

    /**
     * @return class-string[]
     */
    abstract protected function getFiles(): array;

    abstract protected function getName(): string;
}
