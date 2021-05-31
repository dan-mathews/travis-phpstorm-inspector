<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory\Directories;

use TravisPhpstormInspector\IdeaDirectory\CreateInterface;
use TravisPhpstormInspector\IdeaDirectory\DirectoryCreator;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;

class InspectionProfiles implements CreateInterface
{
    public const DIRECTORY_NAME = 'inspectionProfiles';

    /**
     * @var DirectoryCreator
     */
    private $directoryCreator;

    /**
     * @var CreateInterface[]
     */
    private $files;

    /**
     * @var InspectionsXml
     */
    private $inspectionsXml;

    public function __construct(
        DirectoryCreator $directoryCreator,
        ProfileSettingsXml $profileSettingsXml,
        InspectionsXml $inspectionsXml
    ) {
        $this->files[] = $profileSettingsXml;
        $this->files[] = $inspectionsXml;

        $this->inspectionsXml = $inspectionsXml;

        $this->directoryCreator = $directoryCreator;
    }

    protected function getName(): string
    {
        return self::DIRECTORY_NAME;
    }

    public function getInspectionsXmlPath(): string
    {
        return $this->inspectionsXml->getPath();
    }

    public function create(string $location): void
    {
        $this->directoryCreator->createDirectory($location, self::DIRECTORY_NAME, $this->files, []);
    }
}
