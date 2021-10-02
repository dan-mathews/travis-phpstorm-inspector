<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\IdeaDirectory\Directories\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfilesDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

class IdeaDirectoryBuilder
{
    /**
     * @param string $inspectorPath
     * @param InspectionsXml $inspectionsXml
     * @return IdeaDirectory
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function build(
        string $inspectorPath,
        InspectionsXml $inspectionsXml,
        string $phpVersion
    ): IdeaDirectory {
        $profileSettingsXml = new ProfileSettingsXml($inspectionsXml->getProfileNameValue());

        $inspectionProfilesDirectory = new InspectionProfilesDirectory(
            $profileSettingsXml,
            $inspectionsXml
        );

        //TODO use the real project name from location in ModulesXml and ProjectIml
        $modulesXml = new ModulesXml();
        $phpXml = new PhpXml($phpVersion);
        $projectIml = new ProjectIml(InspectCommand::NAME);

        $ideaDirectory = new IdeaDirectory(
            $modulesXml,
            $phpXml,
            $projectIml,
            $inspectionProfilesDirectory
        );

        $ideaDirectory->create($inspectorPath);

        return $ideaDirectory;
    }
}
