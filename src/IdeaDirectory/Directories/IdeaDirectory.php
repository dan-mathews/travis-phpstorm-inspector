<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class IdeaDirectory extends AbstractCreatableDirectory
{
    private const NAME = 'travis-phpstorm-inspector-.idea';

    /**
     * @var bool
     */
    protected $overwrite = true;

    /**
     * @var InspectionProfilesDirectory
     */
    private $inspectionProfilesDirectory;

    public function __construct(
        ModulesXml $modulesXml,
        PhpXml $phpXml,
        ProjectIml $projectIml,
        InspectionProfilesDirectory $inspectionProfilesDirectory
    ) {
        $this->files[] = $modulesXml;
        $this->files[] = $phpXml;
        $this->files[] = $projectIml;

        $this->inspectionProfilesDirectory = $inspectionProfilesDirectory;

        $this->directories[] = $inspectionProfilesDirectory;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getInspectionsXml(): InspectionsXml
    {
        return $this->inspectionProfilesDirectory->getInspectionsXml();
    }
}
