<?php

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\FileContents\GetContentsInterface;

class Directory
{
    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $realPath = realpath($path);

        if (
            false === $realPath ||
            false === is_dir($realPath) ||
            false === is_readable($realPath)
        ) {
            throw new \RuntimeException('Could not find a readable directory at ' . $path);
        }

        $this->path = $realPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function createFile(string $name, GetContentsInterface $contents): self
    {
        $file = fopen($this->path . '/' . $name, 'wb');

        if (false === $file) {
            throw new \RuntimeException('Failed to create file at path: "' . $this->path . '/' . $name . '".');
        }

        $written = fwrite($file, $contents->getContents());

        if (false === $written) {
            throw new \RuntimeException('Failed to write to file at path: "' . $this->path . '/' . $name . '".');
        }

        $closed = fclose($file);

        if (false === $closed) {
            throw new \RuntimeException('Failed to close file at path: "' . $this->path . '/' . $name . '".');
        }

        return $this;
    }

    /**
     * @throws ConfigurationException
     */
    public function createDirectory(string $name, bool $overwrite = false): Directory
    {
        if ($overwrite) {
            if (is_file($this->path . '/' . $name)) {
                throw new ConfigurationException('Cannot overwrite a file with a directory');
            }

            if (is_dir($this->path . '/' . $name)) {
                $this->removeDirectory(new \DirectoryIterator($this->path . '/' . $name));
            }
        }

        if (file_exists($this->path . '/' . $name)) {
            throw new ConfigurationException('File cannot exist before creation: ' . $this->path . '/' . $name);
        }

        if (!mkdir($this->path . '/' . $name) && !is_dir($this->path . '/' . $name)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->path . '/' . $name));
        }

        return new Directory($this->path . '/' . $name);
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
}
