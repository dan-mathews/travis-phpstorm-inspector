<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\IdeaDirectory\Directories\Idea;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfiles;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;

class SimpleIdeaFactory
{
    public function create(string $directoryPath, string $inspectionsXmlPath): Idea
    {
        $ideaDirectory = new Idea($directoryPath);

        $inspectionProfilesDirectory = new InspectionProfiles($ideaDirectory->getPath());

        $inspectionsXml = new InspectionsXml();

        $inspectionsXml->setContentsFromInspectionsXml($inspectionsXmlPath);

        $inspectionProfilesDirectory->addFile($inspectionsXml);

        $ideaDirectory->addDirectory($inspectionProfilesDirectory);

        $ideaDirectory->create();

        return $ideaDirectory;
    }
}
