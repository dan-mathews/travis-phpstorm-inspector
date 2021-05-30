<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class IdeaDirectory extends AbstractDirectory
{
    public const DIRECTORY_NAME = '.idea';

    /**
     * @return class-string[]
     */
    protected function getFiles(): array
    {
        return [
            ModulesXml::class,
            PhpXml::class,
            ProjectIml::class,
        ];
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }
}
