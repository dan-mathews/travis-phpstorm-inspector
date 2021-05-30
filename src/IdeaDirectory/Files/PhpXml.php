<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractFile;

class PhpXml extends AbstractFile
{
    protected function getContents(): string
    {
        $phpLanguageLevel = '7.3';

        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<project version="4">'
        . '<component name="PhpProjectSharedConfiguration" php_language_level="' . $phpLanguageLevel . '">'
        . '<option name="suggestChangeDefaultLanguageLevel" value="false" />'
        . '</component>'
        . '</project>';
    }

    protected function getName(): string
    {
        return 'php.xml';
    }
}
