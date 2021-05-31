<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

class FileCreator
{
    public function createFile(string $location, string $name, string $contents): void
    {
        $file = fopen($location . '/' . $name, 'wb');

        if (false === $file){
            echo 'Unable to open file ' . $name;
            exit(1);
        }

        $written = fwrite($file, $contents);

        if (false === $written){
            echo 'Unable to write file ' . $name;
            exit(1);
        }

        $closed = fclose($file);

        if (false === $closed){
            echo 'Unable to close file ' . $name;
            exit(1);
        }
    }
}