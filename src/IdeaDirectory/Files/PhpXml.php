<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractFile;

class PhpXml extends AbstractFile
{
    private const NAME = 'php.xml';

    /**
     * @var string
     */
    private $phpLanguageLevel;

    public function __construct(string $phpLanguageLevel)
    {
        $this->phpLanguageLevel = $phpLanguageLevel;
    }

    protected function getContents(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<project version="4">'
        . '<component name="PhpProjectSharedConfiguration" php_language_level="' . $this->phpLanguageLevel . '">'
        . '<option name="suggestChangeDefaultLanguageLevel" value="false" />'
        . '</component>'
        . '</project>';
    }

    protected function getName(): string
    {
        return self::NAME;
    }
}
