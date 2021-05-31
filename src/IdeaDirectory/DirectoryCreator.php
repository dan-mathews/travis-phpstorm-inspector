<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

class DirectoryCreator
{
    public function createDirectory(string $location, string $name, array $files, array $directories): void
    {
        $path = $location . '/' . $name;

        $output = [];

        $code = 0;

        exec('mkdir ' . $path, $output, $code);

        if (0 !== $code) {
            echo 'Failed to create directory ' . $name;
            exit(1);
        }

        /** @var CreateInterface $directory */
        foreach ($directories as $directory) {
            $directory->create($path);
        }

        /** @var CreateInterface $directory */
        foreach ($files as $file) {
            $file->create($path);
        }
    }
}
