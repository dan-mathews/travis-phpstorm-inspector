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
    public function __construct(string $path, OutputInterface $output)
    {
        $this->output = $output;

        $realPath = realpath($path);

        if (
            false === $realPath ||
            false === is_dir($realPath) ||
            false === is_readable($realPath)
        ) {
            throw new FilesystemException('Could not find a readable directory at ' . $path);
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
    public function createDirectory(string $name, bool $overwrite = false): Directory
    {
        $absolutePath = $this->path . '/' . $name;

        if (file_exists($absolutePath)) {
            if (false === $overwrite) {
                throw new FilesystemException('Cannot create directory, file already exists at: ' . $absolutePath);
            }

            if (is_file($absolutePath)) {
                throw new FilesystemException('Cannot overwrite a file with a directory');
            }

            if (is_dir($absolutePath)) {
                $directoryIterator = $this->getDirectoryIterator($absolutePath);

                $this->removeDirectory($directoryIterator);
            }
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
    private function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        $directoryPath = $directoryIterator->getPath();

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

                self::removeDirectory($directoryIterator);
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

        // We check the directory still exists before removing, as PhpStorm makes rapid changes to the .idea directory
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
}
