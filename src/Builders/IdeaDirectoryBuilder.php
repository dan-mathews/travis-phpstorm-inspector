<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Commands\InspectCommand;
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
    private const DIRECTORY_IDEA = 'travis-phpstorm-inspector-.idea';
    private const DIRECTORY_INSPECTION_PROFILES = 'inspectionProfiles';
    private const FILE_MODULES_XML = 'modules.xml';
    private const FILE_PHP_XML = 'php.xml';
    private const FILE_PROFILES_SETTINGS = 'profiles_settings.xml';
    private const FILE_PROJECT_IML = 'project.iml';

    /**
     * @var InspectionProfileXml
     */
    private $inspectionsXml;

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
     * @param InspectionProfileXml $inspectionsXml
     * @param string $phpVersion
     * @param array<string> $excludeFolders
     * @throws FilesystemException
     */
    public function __construct(
        Directory $parentDirectory,
        InspectionProfileXml $inspectionsXml,
        string $phpVersion,
        array $excludeFolders
    ) {
        $this->inspectionsXml = $inspectionsXml;
        $this->phpVersion = $phpVersion;
        $this->ideaDirectory = $parentDirectory->getOrCreateSubDirectory(self::DIRECTORY_IDEA);
        $this->ideaDirectory->empty();
        $this->excludeFolders = $excludeFolders;
    }

    /**
     * @throws FilesystemException
     */
    public function build(): void
    {
        $inspectionProfilesDirectory = $this->ideaDirectory->createSubDirectory(self::DIRECTORY_INSPECTION_PROFILES);
        $profileSettingsXml = new ProfileSettingsXml($this->inspectionsXml->getProfileNameValue());
        $inspectionProfilesDirectory
            ->createFile(self::FILE_PROFILES_SETTINGS, $profileSettingsXml)
            ->createFile($this->inspectionsXml->getName(), $this->inspectionsXml);

        $modulesXml = new ModulesXml();
        $phpXml = new PhpXml($this->phpVersion);
        $projectIml = new ProjectIml($this->excludeFolders);

        $this->ideaDirectory
            ->createFile(self::FILE_MODULES_XML, $modulesXml)
            ->createFile(self::FILE_PHP_XML, $phpXml)
            ->createFile(self::FILE_PROJECT_IML, $projectIml);
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->ideaDirectory;
    }
}
