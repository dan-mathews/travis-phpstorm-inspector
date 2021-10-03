<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

class PhpXml implements GetContentsInterface
{
    /**
     * @var string
     */
    private $phpLanguageLevel;

    public function __construct(string $phpLanguageLevel)
    {
        $this->phpLanguageLevel = $phpLanguageLevel;
    }

    public function getContents(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<project version="4">'
        . '<component name="PhpProjectSharedConfiguration" php_language_level="' . $this->phpLanguageLevel . '">'
        . '<option name="suggestChangeDefaultLanguageLevel" value="false" />'
        . '</component>'
        . '</project>';
    }
}
