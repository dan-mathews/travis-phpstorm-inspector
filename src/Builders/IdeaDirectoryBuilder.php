<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;
use TravisPhpstormInspector\FileContents\ModulesXml;
use TravisPhpstormInspector\FileContents\PhpXml;
use TravisPhpstormInspector\FileContents\ProfileSettingsXml;
use TravisPhpstormInspector\FileContents\ProjectIml;

/**
 * @implements BuilderInterface<Directory>
 */
class IdeaDirectoryBuilder implements BuilderInterface
{
    public const DIRECTORY_IDEA = '.idea';
    public const DIRECTORY_INSPECTION_PROFILES = 'inspectionProfiles';

    private const FILE_MODULES_XML = 'modules.xml';
    private const FILE_PHP_XML = 'php.xml';
    private const FILE_PROFILES_SETTINGS = 'profiles_settings.xml';
    private const FILE_PROJECT_IML = 'project.iml';

    /**
     * @var InspectionProfileXml
     */
    private $inspectionsXmlContents;

    /**
     * @var string
     */
    private $phpVersion;

    /**
     * @var Directory
     */
    private $ideaDirectory;

    /**
     * @var array<string>
     */
    private $excludeFolders;

    /**
     * @param Directory $parentDirectory
     * @param InspectionProfileXml $inspectionsXmlContents
     * @param string $phpVersion
     * @param array<string> $excludeFolders
     * @throws FilesystemException
     */
    public function __construct(
        Directory $parentDirectory,
        InspectionProfileXml $inspectionsXmlContents,
        string $phpVersion,
        array $excludeFolders
    ) {
        $this->inspectionsXmlContents = $inspectionsXmlContents;
        $this->phpVersion = $phpVersion;
        $this->ideaDirectory = $parentDirectory->setOrCreateSubDirectory(self::DIRECTORY_IDEA);
        $this->ideaDirectory->empty();
        $this->excludeFolders = $excludeFolders;
    }

    /**
     * @throws FilesystemException
     */
    public function build(): void
    {
        // Make/Create an 'inspectionProfiles' directory and fill it with the needed files.
        $inspectionProfilesDirectory = $this->ideaDirectory->createSubDirectory(self::DIRECTORY_INSPECTION_PROFILES);
        $profileSettingsXmlContents = new ProfileSettingsXml($this->inspectionsXmlContents->getProfileNameValue());
        $inspectionProfilesDirectory
            ->createFile(self::FILE_PROFILES_SETTINGS, $profileSettingsXmlContents)
            ->createFile($this->inspectionsXmlContents->getName(), $this->inspectionsXmlContents);

        // Fill the '.idea' directory with the needed files.
        $modulesXmlContents = new ModulesXml();
        $phpXmlContents = new PhpXml($this->phpVersion);
        $projectImlContents = new ProjectIml($this->excludeFolders);
        $this->ideaDirectory
            ->createFile(self::FILE_MODULES_XML, $modulesXmlContents)
            ->createFile(self::FILE_PHP_XML, $phpXmlContents)
            ->createFile(self::FILE_PROJECT_IML, $projectImlContents);
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->ideaDirectory;
    }
}
