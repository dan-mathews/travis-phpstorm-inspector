<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\FileCreator;

class PhpXml implements CreateInterface
{
    private const NAME = 'php.xml';

    /**
     * @var FileCreator
     */
    private $fileCreator;

    /**
     * @var string
     */
    private $phpLanguageLevel;

    public function __construct(FileCreator $fileCreator, string $phpLanguageLevel)
    {
        $this->fileCreator = $fileCreator;

        $this->phpLanguageLevel = $phpLanguageLevel;
    }

    private function getContents(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<project version="4">'
        . '<component name="PhpProjectSharedConfiguration" php_language_level="' . $this->phpLanguageLevel . '">'
        . '<option name="suggestChangeDefaultLanguageLevel" value="false" />'
        . '</component>'
        . '</project>';
    }

    public function create(string $location): void
    {
        $this->fileCreator->createFile($location, self::NAME, $this->getContents());
    }
}
