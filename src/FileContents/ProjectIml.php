<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

class ProjectIml implements GetContentsInterface
{
    /**
     * @var array<string>
     */
    private $excludeFolderPaths;

    /**
     * @param array<string> $excludeFolderPaths
     */
    public function __construct(array $excludeFolderPaths)
    {
        $this->excludeFolderPaths = $excludeFolderPaths;
    }

    public function getContents(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
        . '<module type="WEB_MODULE" version="4">'
        . '<component name="NewModuleRootManager">'
        . '<content url="file://$MODULE_DIR$">'
        . $this->constructExcludeFoldersXmlTags()
        . '</content>'
        . '</component>'
        . '</module>';
    }

    private function constructExcludeFoldersXmlTags(): string
    {
        $excludeFoldersXmlTags = '';

        foreach ($this->excludeFolderPaths as $excludeFolderPath) {
            $excludeFoldersXmlTags .= '<excludeFolder url="file://$MODULE_DIR$/' . $excludeFolderPath . '" />';
        }

        return $excludeFoldersXmlTags;
    }
}
