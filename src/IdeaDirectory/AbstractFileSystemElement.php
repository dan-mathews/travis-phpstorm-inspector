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
            throw new \LogicException($this->getName() . ' must be created before the path is retrieved.');
        }

        return $this->path;
    }

    abstract public function create(string $location): void;
}
