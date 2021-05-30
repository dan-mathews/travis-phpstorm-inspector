<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\App;
use TravisPhpstormInspector\IdeaDirectory\AbstractIdeaFile;

class ProjectIml extends AbstractIdeaFile
{
    protected function getContents(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<module type="WEB_MODULE" version="4">'
        . '<component name="NewModuleRootManager">'
        . '<content url="file://$MODULE_DIR$">'
        . '<excludeFolder url="file://$MODULE_DIR$/' . App::NAME . '" />'
        . '</content>'
        . '</component>'
        . '</module>';
    }

    protected function getName(): string
    {
        return 'project.iml';
    }
}
