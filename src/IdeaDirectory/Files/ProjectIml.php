<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractFile;

class ProjectIml extends AbstractFile
{
    private const NAME = 'project.iml';

    /**
     * @var string
     */
    private $appName;

    public function __construct(string $appName)
    {
        $this->appName = $appName;
    }

    protected function getContents(): string
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

    protected function getName(): string
    {
        return self::NAME;
    }
}
