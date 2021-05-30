<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;

class InspectionProfilesDirectory extends AbstractDirectory
{
    public const DIRECTORY_NAME = 'inspectionProfiles';

    public function __construct()
    {
        $this->files[] = new ProfileSettingsXml();
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }
}
