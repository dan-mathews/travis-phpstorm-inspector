<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class Idea extends AbstractDirectory
{
    public const NAME = '.idea';

    /**
     * @var InspectionProfiles
     */
    private $inspectionProfiles;

    public function __construct(
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
    }

    protected function getName(): string
    {
        return self::NAME;
    }

    public function getInspectionsXmlPath(): string
    {
        return $this->inspectionProfiles->getInspectionsXmlPath();
    }
}
