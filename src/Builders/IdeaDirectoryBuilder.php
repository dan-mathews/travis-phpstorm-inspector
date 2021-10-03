<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\FileContents\InspectionsXml;
use TravisPhpstormInspector\FileContents\ModulesXml;
use TravisPhpstormInspector\FileContents\PhpXml;
use TravisPhpstormInspector\FileContents\ProfileSettingsXml;
use TravisPhpstormInspector\FileContents\ProjectIml;

/**
 * @implements BuilderInterface<IdeaDirectory>
 */
class IdeaDirectoryBuilder implements BuilderInterface
{
    private const DIRECTORY_NAME_IDEA = 'travis-phpstorm-inspector-.idea';

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

    public function __construct(
        Directory $inspectorDirectory,
        InspectionsXml $inspectionsXml,
        string $phpVersion
    ) {
        $this->inspectionsXml = $inspectionsXml;
        $this->phpVersion = $phpVersion;
        $this->ideaDirectory = $inspectorDirectory->createDirectory(self::DIRECTORY_NAME_IDEA, true);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function build(): void
    {
        $inspectionProfilesDirectory = $this->ideaDirectory->createDirectory('inspectionProfiles');
        $profileSettingsXml = new ProfileSettingsXml($this->inspectionsXml->getProfileNameValue());
        $inspectionProfilesDirectory
            ->createFile($this->inspectionsXml->getProfileNameValue(), $profileSettingsXml)
            ->createFile($this->inspectionsXml->getName(), $this->inspectionsXml);

        //TODO use the real project name from location in ModulesXml and ProjectIml
        $modulesXml = new ModulesXml();
        $phpXml = new PhpXml($this->phpVersion);
        $projectIml = new ProjectIml(InspectCommand::NAME);

        $this->ideaDirectory
            ->createFile('modules.xml', $modulesXml)
            ->createFile('php.xml', $phpXml)
            ->createFile('project.iml', $projectIml);
    }

    public function getResult(): object
    {
        return $this->ideaDirectory;
    }
}
