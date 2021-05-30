<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class IdeaDirectory
{
    public const DIR_NAME = '.idea';

    /**
     * @var class-string[]
     */
    private const FILES = [
        ModulesXml::class,
        PhpXml::class,
        ProjectIml::class,
    ];

    public function create(string $projectRoot)
    {
        $path = $projectRoot . '/' . self::DIR_NAME;

        echo $path;

        exec('mkdir ' . $path);

        /* @var AbstractIdeaFile $fileClass */
        foreach (self::FILES as $fileClass) {
            $ideaFile = new $fileClass();

            $ideaFile->create($path);
        }
    }
}
