<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Files;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableFile;

class ProjectIml extends AbstractCreatableFile
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
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<module type="WEB_MODULE" version="4">'
        . '<component name="NewModuleRootManager">'
        . '<content url="file://$MODULE_DIR$">'
        . '<excludeFolder url="file://$MODULE_DIR$/' . $this->appName . '" />'
        . '</content>'
        . '</component>'
        . '</module>';
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
