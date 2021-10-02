<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

//todo merge with Directory or use symfony filesystem
class CreatableDirectory extends AbstractCreatableFileSystemElement
{
    /**
     * @var AbstractCreatableFile[]
     */
    protected $files = [];

    /**
     * @var CreatableDirectory[]
     */
    protected $directories = [];

    /**
     * @var bool
     */
    protected $overwrite = true;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $location
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function create(string $location): void
    {
        parent::create($location);

        if (file_exists($this->getPath())) {
            if (true === $this->overwrite) {
                $this->removeDirectory(new \DirectoryIterator($this->getPath()));
            } else {
                throw new \InvalidArgumentException(
                    'Project must not contain a file or directory at path: "' . $this->getPath() . '".'
                );
            }
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

    private function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        foreach ($directoryIterator as $info) {
            if ($info->isDot()) {
                continue;
            }

            $realPath = $info->getRealPath();

            if (false === $realPath) {
                throw new \RuntimeException('Could not get real path of ' . var_export($info, true));
            }

            if ($info->isDir()) {
                self::removeDirectory(new \DirectoryIterator($realPath));
                continue;
            }

            if ($info->isFile()) {
                unlink($realPath);
            }
        }

        rmdir($directoryIterator->getPath());
    }

    public function addFile(AbstractCreatableFile $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function addDirectory(CreatableDirectory $directory): self
    {
        $this->directories[] = $directory;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}