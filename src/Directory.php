<?php

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\GetContentsInterface;

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
     * @throws FilesystemException
     */
    public function __construct(
        string $absolutePath,
        OutputInterface $output,
        bool $createIfNotFound = false
    ) {
        if ('' === $absolutePath) {
            throw new FilesystemException('Cannot construct a Directory with an empty path');
        }

        $this->output = $output;

        $realPath = realpath($absolutePath);

        if (!$this->isDirectoryReadable($realPath)) {
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

        $file = fopen($absolutePath, 'wb');

        if (false === $file) {
            throw new FilesystemException('Failed to create file at path: "' . $absolutePath . '".');
        }

        if (false === fwrite($file, $contents->getContents())) {
            throw new FilesystemException('Failed to write to file at path: "' . $absolutePath . '".');
        }

        if (false === fclose($file)) {
            throw new FilesystemException('Failed to close file at path: "' . $absolutePath . '".');
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

        if (file_exists($absolutePath)) {
            throw new FilesystemException('Cannot create directory, file already exists at: ' . $absolutePath);
        }

        if (false === mkdir($absolutePath) && false === is_dir($absolutePath)) {
            throw new FilesystemException(sprintf('Directory "%s" was not created', $absolutePath));
        }

        $this->output->writeln('Created directory ' . $absolutePath);

        return new Directory($absolutePath, $this->output);
    }

    /**
     * @throws FilesystemException
     */
    public function createSubDirectory(string $name): Directory
    {
        $absolutePath = $this->path . '/' . $name;

        return $this->createDirectory($absolutePath);
    }

    /**
     * @throws FilesystemException
     */
    public function getOrCreateSubDirectory(string $name): Directory
    {
        $absolutePath = $this->path . '/' . $name;

        return new Directory($absolutePath, $this->output, true);
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
    private function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        $directoryPath = $directoryIterator->getPath();

        $this->emptyDirectory($directoryIterator);

        if (false === rmdir($directoryPath)) {
            throw new FilesystemException('Could not remove directory at path ' . $directoryPath);
        }

        $this->output->writeln('Removed directory ' . $directoryPath);
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
     * @param false|string $realPath
     * @return bool
     */
    private function isDirectoryReadable($realPath): bool
    {
        return
            true === is_string($realPath) &&
            true === is_dir($realPath) &&
            true === is_readable($realPath);
    }

    /**
     * @throws FilesystemException
     */
    private function emptyDirectory(\DirectoryIterator $directoryIterator)
    {
        foreach ($directoryIterator as $info) {
            if ($info->isDot()) {
                continue;
            }

            $filePath = $info->getRealPath();

            if (false === $filePath) {
                throw new FilesystemException('Could not get real path of ' . $info->getPath());
            }

            if ($info->isDir()) {
                $directoryIterator = $this->getDirectoryIterator($filePath);

                $this->removeDirectory($directoryIterator);
                continue;
            }

            if (
                $info->isFile() &&
                false === unlink($filePath)
            ) {
                throw new FilesystemException('Could not remove file at path ' . $filePath);
            }

            $this->output->writeln('Removed file ' . $filePath);
        }
    }

    /**
     * @throws \RuntimeException
     */
    public function copyTo(Directory $directory, array $excludeFolders, CommandRunner $commandRunner): void
    {
        //TODO create a wrapper class for the exec command and add a comment to explain why symfony Process doesn't work
        // for this project. Then move this and the docker facade execs to that and remove symfony Process.

        $excludeString = '';

        foreach ($excludeFolders as $folder) {
            $excludeString .= '--exclude \'' . $folder . '\' ';
        }

        //todo name each command to represent what it does
        //todo follow the ->addCommand() pattern from dockerFacade
        //todo make a cache class to keep all this logic and hold relevant dirs
        //todo: strip these excludes back so they make sense in flashcard context. Solve self-analysis another time
        $command = 'rsync -a ' . $excludeString . $this->path . '/ ' . $directory->getPath();

        $commandRunner->run($command);
    }
}
