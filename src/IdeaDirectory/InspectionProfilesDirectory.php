<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;

class InspectionProfilesDirectory extends AbstractDirectory
{
    public const DIRECTORY_NAME = 'inspectionProfiles';

    /**
     * @return class-string[]
     */
    protected function getFiles(): array
    {
        return [
            ProfileSettingsXml::class,
        ];
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }
}
