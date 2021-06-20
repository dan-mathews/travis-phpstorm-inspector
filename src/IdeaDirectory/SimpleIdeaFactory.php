<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\App;
use TravisPhpstormInspector\IdeaDirectory\Directories\Idea;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfiles;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;
use TravisPhpstormInspector\Project;

class SimpleIdeaFactory
{
    public function create(Project $project, string $inspectionsXmlPath): Idea
    {
        $inspectionsXml = new InspectionsXml($inspectionsXmlPath);
        $profileSettingsXml = new ProfileSettingsXml($inspectionsXml->getProfileNameValue());

        $inspectionProfilesDirectory = new InspectionProfiles(
            $profileSettingsXml,
            $inspectionsXml
        );

        //TODO use the real project name from location in ModulesXml and ProjectIml
        $modulesXml = new ModulesXml();
        //TODO read the language level from config
        $phpXml = new PhpXml('7.3');
        $projectIml = new ProjectIml(App::NAME);

        $ideaDirectory = new Idea(
            $modulesXml,
            $phpXml,
            $projectIml,
            $inspectionProfilesDirectory
        );

        $ideaDirectory->create($project->getPath());

        return $ideaDirectory;
    }
}
