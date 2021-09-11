<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;

class InspectionProfilesDirectory extends AbstractCreatableDirectory
{
    private const NAME = 'inspectionProfiles';

    /**
     * @var InspectionsXml
     */
    private $inspectionsXml;

    public function __construct(
        ProfileSettingsXml $profileSettingsXml,
        InspectionsXml $inspectionsXml
    ) {
        $this->files[] = $profileSettingsXml;
        $this->files[] = $inspectionsXml;

        $this->inspectionsXml = $inspectionsXml;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getInspectionsXml(): InspectionsXml
    {
        return $this->inspectionsXml;
    }
}
