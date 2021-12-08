<?php

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\GetContentsInterface;

/**
 * This is not a perfect class for Directory management by any means.
 * Amongst other things, the subDirectories array property should probably be populated on construction.
 * TODO: look further into open source alternatives for this filesystem management.
 */
class Directory
{
    /**
     * @var string
     */
    private $path;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var array<array-key, Directory>
     */
    private $subDirectories = [];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @throws FilesystemException
     */
    public function __construct(
        string $absolutePath,
        OutputInterface $output,
        Filesystem $filesystem,
        bool $createIfNotFound = false
    ) {
        if ('' === $absolutePath) {
            throw new FilesystemException('Cannot construct a Directory with an empty path');
        }

        $this->filesystem = $filesystem;

        $this->output = $output;

        $realPath = realpath($absolutePath);

        if (
            false === is_string($realPath) ||
            false === is_dir($realPath) ||
            false === is_readable($realPath)
        ) {
            if (false === $createIfNotFound) {
                throw new FilesystemException('Could not find a readable directory at ' . $absolutePath);
            }

            $realPath = $this->createDirectory($absolutePath)->getPath();
        }

        $this->path = $realPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @throws FilesystemException
     */
    public function createFile(string $name, GetContentsInterface $contents): self
    {
        $absolutePath = $this->path . '/' . $name;

        try {
            $this->filesystem->dumpFile($absolutePath, $contents->getContents());
        } catch (IOException $e) {
            throw new FilesystemException('Failed to create file at path: "' . $absolutePath . '".', 2, $e);
        }

        $this->output->writeln('Created file ' . $absolutePath);

        return $this;
    }

    /**
     * @throws FilesystemException
     */
    private function createDirectory(string $absolutePath): Directory
    {
        if ('' === $absolutePath) {
            throw new FilesystemException('Cannot create a directory with an empty path');
        }

        try {
            if ($this->filesystem->exists($absolutePath)) {
                throw new FilesystemException('Cannot create directory, file already exists at: ' . $absolutePath);
            }

            $this->filesystem->mkdir($absolutePath);
        } catch (IOException $e) {
            throw new FilesystemException('Could not create directory at ' . $absolutePath, 2, $e);
        }

        $this->output->writeln('Created directory ' . $absolutePath);

        return new Directory($absolutePath, $this->output, $this->filesystem);
    }

    /**
     * @throws FilesystemException
     */
    public function createSubDirectory(string $name): Directory
    {
        $absolutePath = $this->path . '/' . $name;

        $subDirectory = $this->createDirectory($absolutePath);

        $this->subDirectories[$name] = $subDirectory;

        return $subDirectory;
    }

    /**
     * @throws FilesystemException
     */
    public function setOrCreateSubDirectory(string $name): Directory
    {
        $absolutePath = $this->path . '/' . $name;

        $subDirectory = new Directory($absolutePath, $this->output, $this->filesystem, true);

        $this->subDirectories[$name] = $subDirectory;

        return $subDirectory;
    }

    /**
     * @throws FilesystemException
     */
    public function getSubDirectory(string $name): Directory
    {
        if (!isset($this->subDirectories[$name])) {
            throw new FilesystemException('No ' . $name . ' directory found in ' . $this->getPath());
        }

        return $this->subDirectories[$name];
    }

    /**
     * @throws FilesystemException
     */
    public function empty(): void
    {
        $directoryIterator = $this->getDirectoryIterator($this->path);

        $this->emptyDirectory($directoryIterator);
    }

    /**
     * @throws FilesystemException
     */
    public function removeSubDirectory(string $name): void
    {
        $absolutePath = $this->path . '/' . $name;

        try {
            $this->filesystem->remove($absolutePath);
        } catch (IOException $e) {
            throw new FilesystemException('Could not remove directory ' . $name . ' from ' . $this->getPath(), 2, $e);
        }

        unset($this->subDirectories[$name]);
    }

    /**
     * @throws FilesystemException
     */
    private function getDirectoryIterator(string $path): \DirectoryIterator
    {
        try {
            return new \DirectoryIterator($path);
        } catch (\UnexpectedValueException | \RuntimeException $e) {
            throw new FilesystemException('Could not iterate over directory at path ' . $path, 0, $e);
        }
    }

    /**
     * @throws FilesystemException
     */
    private function emptyDirectory(\DirectoryIterator $directoryIterator): void
    {
        foreach ($directoryIterator as $info) {
            if ($info->isDot()) {
                continue;
            }

            $filePath = $info->getRealPath();

            if (false === $filePath) {
                throw new FilesystemException('Could not get real path of ' . $info->getPath());
            }

            try {
                $this->filesystem->remove($filePath);
            } catch (IOException $e) {
                throw new FilesystemException('Could not remove file at path ' . $filePath, 2, $e);
            }

            $this->output->writeln('Removed file ' . $filePath);
        }
    }

    /**
     * This differs from Symfony's mirror() in that you can add directories to exclude.
     * @param Directory $directory
     * @param array<int, string> $excludeDirectories
     * @param CommandRunner $commandRunner
     * @throws FilesystemException
     */
    public function copyTo(Directory $directory, array $excludeDirectories, CommandRunner $commandRunner): void
    {
        $excludeString = '';

        foreach ($excludeDirectories as $excludeDirectory) {
            $excludeString .= '--exclude \'' . $excludeDirectory . '\' ';
        }

        $rsyncCommand = 'rsync -aW --delete ' . $excludeString . $this->path . '/ ' . $directory->getPath();

        try {
            $commandRunner->run($rsyncCommand);
        } catch (\RuntimeException $e) {
            throw new FilesystemException('Could not copy directory', 1, $e);
        }
    }
}
