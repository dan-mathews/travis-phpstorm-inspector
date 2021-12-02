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
     * @var array<array-key, Directory>
     */
    private $subDirectories = [];

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

        $subDirectory = $this->createDirectory($absolutePath);

        $this->subDirectories[$name] = $subDirectory;

        return $subDirectory;
    }

    /**
     * todo merge with createSubDirectory() with a flag.
     *  keep createDirectory() pure but have new Directory throw an error then catch if flag is set
     * @throws FilesystemException
     */
    public function setOrCreateSubDirectory(string $name): Directory
    {
        $absolutePath = $this->path . '/' . $name;

        $subDirectory = new Directory($absolutePath, $this->output, true);

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
     * todo keep object properties in line with the real filesystem - remove all directories in subDirectories arr. here
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

        //todo should be able to look in subDirectories array here if it's populated on creation
        if (!is_dir($absolutePath)) {
            return;
        }

        $directoryIterator = $this->getDirectoryIterator($absolutePath);

        $this->removeDirectory($directoryIterator);

        unset($this->subDirectories[$name]);
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
     * @param Directory $directory
     * @param array<int, string> $excludeFolders
     * @param CommandRunner $commandRunner
     * @throws FilesystemException
     */
    public function copyTo(Directory $directory, array $excludeFolders, CommandRunner $commandRunner): void
    {
        //TODO create a wrapper class for the exec command and add a comment to explain why symfony Process doesn't work
        // for this project. Then move this and the docker facade execs to that and remove symfony Process.

        $excludeString = '';

        foreach ($excludeFolders as $folder) {
            $excludeString .= '--exclude \'' . $folder . '\' ';
        }

        //todo follow the ->addCommand() pattern from dockerFacade
        //todo make a cache class to keep all this logic and hold relevant dirs
        //todo: strip these excludes back so they make sense in flashcard context. Solve self-analysis another time
        $rsyncCommand = 'rsync -a ' . $excludeString . $this->path . '/ ' . $directory->getPath();

        try {
            $commandRunner->run($rsyncCommand);
        } catch (\RuntimeException $e) {
            throw new FilesystemException('Could not copy directory', 1, $e);
        }
    }
}
