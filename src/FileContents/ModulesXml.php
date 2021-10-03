<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

use TravisPhpstormInspector\FileContents\GetContentsInterface;

class ModulesXml implements GetContentsInterface
{
    private const NAME = 'modules.xml';

    public function getContents(): string
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

    public function getName(): string
    {
        return self::NAME;
    }
}
