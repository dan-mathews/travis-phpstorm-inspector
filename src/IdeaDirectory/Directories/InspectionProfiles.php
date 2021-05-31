<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;

class InspectionProfiles extends AbstractDirectory
{
    public const DIRECTORY_NAME = 'inspectionProfiles';

    public function __construct(string $parentDirectoryPath)
    {
        $this->files[] = new ProfileSettingsXml();

        parent::__construct($parentDirectoryPath);
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }
}
