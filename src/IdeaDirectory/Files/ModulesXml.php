<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\FileCreator;

class ModulesXml implements CreateInterface
{
    private const NAME = 'modules.xml';

    /**
     * @var FileCreator
     */
    private $fileCreator;

    public function __construct(FileCreator $fileCreator)
    {
        $this->fileCreator = $fileCreator;
    }

    private function getContents(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<project version="4">'
        . '<component name="ProjectModuleManager">'
        . '<modules>'
        . '<module fileurl="file://$PROJECT_DIR$/.idea/project.iml" filepath="$PROJECT_DIR$/.idea/project.iml" />'
        . '</modules>'
        . '</component>'
        . '</project>';
    }

    public function create(string $location): void
    {
        $this->fileCreator->createFile($location, self::NAME, $this->getContents());
    }
}
