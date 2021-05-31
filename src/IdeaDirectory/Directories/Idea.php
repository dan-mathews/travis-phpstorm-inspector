<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\DirectoryCreator;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class Idea implements CreateInterface
{
    public const DIRECTORY_NAME = '.idea';

    /**
     * @var DirectoryCreator
     */
    private $directoryCreator;

    /**
     * @var CreateInterface[]
     */
    private $files;

    /**
     * @var CreateInterface[]
     */
    private $directories;

    /**
     * @var InspectionProfiles
     */
    private $inspectionProfiles;

    public function __construct(
        DirectoryCreator $directoryCreator,
        ModulesXml $modulesXml,
        PhpXml $phpXml,
        ProjectIml $projectIml,
        InspectionProfiles $inspectionProfiles
    ) {
        $this->files[] = $modulesXml;
        $this->files[] = $phpXml;
        $this->files[] = $projectIml;

        $this->inspectionProfiles = $inspectionProfiles;

        $this->directories[] = $inspectionProfiles;

        $this->directoryCreator = $directoryCreator;
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }

    public function getInspectionsXmlPath(): string
    {
        return $this->inspectionProfiles->getInspectionsXmlPath();
    }

    public function create(string $location): void
    {
        $this->directoryCreator->createDirectory($location, self::DIRECTORY_NAME, $this->files, $this->directories);
    }
}
