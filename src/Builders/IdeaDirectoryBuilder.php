<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\InspectionsXml;
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
    private const FILE_PROJECT_IML = 'project.iml';

    /**
     * @var InspectionsXml
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
     * @throws FilesystemException
     */
    public function __construct(
        Directory $inspectorDirectory,
        InspectionsXml $inspectionsXml,
        string $phpVersion
    ) {
        $this->inspectionsXml = $inspectionsXml;
        $this->phpVersion = $phpVersion;
        $this->ideaDirectory = $inspectorDirectory->createDirectory(self::DIRECTORY_IDEA, true);
    }

    /**
     * @throws FilesystemException
     */
    public function build(): void
    {
        $inspectionProfilesDirectory = $this->ideaDirectory->createDirectory(self::DIRECTORY_INSPECTION_PROFILES);
        $profileSettingsXml = new ProfileSettingsXml($this->inspectionsXml->getProfileNameValue());
        $inspectionProfilesDirectory
            ->createFile($this->inspectionsXml->getProfileNameValue(), $profileSettingsXml)
            ->createFile($this->inspectionsXml->getName(), $this->inspectionsXml);

        $modulesXml = new ModulesXml();
        $phpXml = new PhpXml($this->phpVersion);
        $projectIml = new ProjectIml(InspectCommand::NAME);

        $this->ideaDirectory
            ->createFile(self::FILE_MODULES_XML, $modulesXml)
            ->createFile(self::FILE_PHP_XML, $phpXml)
            ->createFile(self::FILE_PROJECT_IML, $projectIml);
    }

    public function getResult(): object
    {
        return $this->ideaDirectory;
    }
}
