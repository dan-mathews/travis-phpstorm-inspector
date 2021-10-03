<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

use TravisPhpstormInspector\FileContents\GetContentsInterface;

class ProjectIml implements GetContentsInterface
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

    public function getContents(): string
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
