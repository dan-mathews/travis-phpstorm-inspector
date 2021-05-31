<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\FileCreator;

class ProjectIml implements CreateInterface
{
    private const NAME = 'project.iml';

    /**
     * @var FileCreator
     */
    private $fileCreator;

    /**
     * @var string
     */
    private $appName;

    public function __construct(FileCreator $fileCreator, string $appName)
    {
        $this->fileCreator = $fileCreator;

        $this->appName = $appName;
    }

    private function getContents(): string
    {
        //TODO add exclude for java and phpstorm folders here too from a const in App and add excludes from config in future
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<module type="WEB_MODULE" version="4">'
        . '<component name="NewModuleRootManager">'
        . '<content url="file://$MODULE_DIR$">'
        . '<excludeFolder url="file://$MODULE_DIR$/' . $this->appName . '" />'
        . '</content>'
        . '</component>'
        . '</module>';
    }

    public function create(string $location): void
    {
        $this->fileCreator->createFile($location, self::NAME, $this->getContents());
    }
}
