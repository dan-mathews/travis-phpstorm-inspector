<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class Idea extends AbstractCreatableDirectory
{
    private const NAME = '.idea';

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

    public function getName(): string
    {
        return self::NAME;
    }

    public function getInspectionsXml(): InspectionsXml
    {
        return $this->inspectionProfiles->getInspectionsXml();
    }
}
