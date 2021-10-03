<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

class ModulesXml implements GetContentsInterface
{
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
}
