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

class SimpleIdeaFactory
{
    public function create(string $location, string $inspectionsXmlPath): Idea
    {
        $fileCreator = new FileCreator();
        $directoryCreator = new DirectoryCreator();

        $inspectionsXml = new InspectionsXml($fileCreator, $inspectionsXmlPath);
        $profileSettingsXml = new ProfileSettingsXml($fileCreator, $inspectionsXml->getProfileNameValue());

        $inspectionProfilesDirectory = new InspectionProfiles(
            $directoryCreator,
            $profileSettingsXml,
            $inspectionsXml
        );

        $modulesXml = new ModulesXml($fileCreator);
        //TODO read the language level from config
        $phpXml = new PhpXml($fileCreator, '7.3');
        $projectIml = new ProjectIml($fileCreator, App::NAME);

        $ideaDirectory = new Idea(
            $directoryCreator,
            $modulesXml,
            $phpXml,
            $projectIml,
            $inspectionProfilesDirectory
        );

        $ideaDirectory->create($location);

        return $ideaDirectory;
    }
}
