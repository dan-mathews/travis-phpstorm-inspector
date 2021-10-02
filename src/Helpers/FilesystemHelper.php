<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Helpers;

use TravisPhpstormInspector\Directory;

class FilesystemHelper
{
    static function makeDirectory(string $path): Directory
    {
        if (!mkdir($path) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $path));
        }

        return new Directory($path);
    }
}
