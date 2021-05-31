<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\AbstractDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;

class InspectionProfiles extends AbstractDirectory
{
    public const NAME = 'inspectionProfiles';

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

    protected function getName(): string
    {
        return self::NAME;
    }

    public function getInspectionsXmlPath(): string
    {
        return $this->inspectionsXml->getPath();
    }
}
