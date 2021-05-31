<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class Idea extends AbstractDirectory
{
    /**
     * @var string
     */
    private $inspectionsXmlPath;

    public const DIRECTORY_NAME = '.idea';

    public function __construct(string $parentDirectoryPath)
    {
        $this->files[] = new ModulesXml();
        $this->files[] = new PhpXml();
        $this->files[] = new ProjectIml();

        parent::__construct($parentDirectoryPath);
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }

    public function setInspectionsXmlPath(string $path): void
    {
        $this->inspectionsXmlPath = $path;
    }

    public function getInspectionsXmlPath(): string
    {
        if (!isset($this->inspectionsXmlPath)){
            echo 'inspections xml has not been set correctly before retrieval';
            exit(1);
        }

        return $this->inspectionsXmlPath;
    }
}
