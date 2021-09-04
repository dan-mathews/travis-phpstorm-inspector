<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\App;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\IdeaDirectory\Directories\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfilesDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;
use TravisPhpstormInspector\ProjectDirectory;

class IdeaDirectoryBuilder
{
    /**
     * @param ProjectDirectory $project
     * @param string $inspectionsXmlPath
     * @return IdeaDirectory
     * @throws \InvalidArgumentException
     * @throws InspectionsProfileException
     */
    public function build(ProjectDirectory $project, string $inspectionsXmlPath): IdeaDirectory
    {
        $inspectionsXml = new InspectionsXml($inspectionsXmlPath);
        $profileSettingsXml = new ProfileSettingsXml($inspectionsXml->getProfileNameValue());

        $inspectionProfilesDirectory = new InspectionProfilesDirectory(
            $profileSettingsXml,
            $inspectionsXml
        );

        //TODO use the real project name from location in ModulesXml and ProjectIml
        $modulesXml = new ModulesXml();
        //TODO read the language level from config
        $phpXml = new PhpXml('7.3');
        $projectIml = new ProjectIml(App::NAME);

        $ideaDirectory = new IdeaDirectory(
            $modulesXml,
            $phpXml,
            $projectIml,
            $inspectionProfilesDirectory
        );

        $ideaDirectory->create($project->getPath());

        return $ideaDirectory;
    }
}
