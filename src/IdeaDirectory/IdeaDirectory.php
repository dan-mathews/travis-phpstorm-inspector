<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class IdeaDirectory extends AbstractDirectory
{
    public const DIRECTORY_NAME = '.idea';

    public function __construct()
    {
        $this->files[] = new ModulesXml();
        $this->files[] = new PhpXml();
        $this->files[] = new ProjectIml();
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }
}
