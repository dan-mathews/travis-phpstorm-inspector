<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

abstract class AbstractFileSystemElement
{
    /**
     * @var null|string
     */
    protected $path;

    public function getPath(): string
    {
        if (null === $this->path){
            echo $this->getName() . ' must be created before the path is retrieved.';
            exit(1);
        }

        return $this->path;
    }

    abstract public function create(string $location): void;
}
