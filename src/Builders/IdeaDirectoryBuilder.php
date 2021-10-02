<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\IdeaDirectory\CreatableDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

/**
 * @implements BuilderInterface<Directory>
 */
class IdeaDirectoryBuilder implements BuilderInterface
{
    public const DIRECTORY_IDEA = 'travis-phpstorm-inspector-.idea';
    public const DIRECTORY_INSPECTIONS_PROFILE = 'inspectionProfiles';

    /**
     * @var CreatableDirectory
     */
    public $ideaDirectory;
    /**
     * @var string
     */
    private $inspectorPath;
    /**
     * @var InspectionsXml
     */
    private $inspectionsXml;
    /**
     * @var string
     */
    private $phpVersion;

    /**
     * @param string $inspectorPath
     * @param InspectionsXml $inspectionsXml
     * @param string $phpVersion
     */
    public function __construct(string $inspectorPath, InspectionsXml $inspectionsXml, string $phpVersion)
    {
        $this->ideaDirectory = new CreatableDirectory(self::DIRECTORY_IDEA);
        $this->inspectorPath = $inspectorPath;
        $this->inspectionsXml = $inspectionsXml;
        $this->phpVersion = $phpVersion;
    }

    public function build(): void
    {
        $profileSettingsXml = new ProfileSettingsXml($this->inspectionsXml->getProfileNameValue());

        $inspectionProfilesDirectory = new CreatableDirectory(self::DIRECTORY_INSPECTIONS_PROFILE);

        $inspectionProfilesDirectory->addFile($profileSettingsXml);

        //TODO use the real project name from location in ModulesXml and ProjectIml
        $modulesXml = new ModulesXml();
        $phpXml = new PhpXml($this->phpVersion);
        $projectIml = new ProjectIml(InspectCommand::NAME);

        $this->ideaDirectory
            ->addFile($modulesXml)
            ->addFile($phpXml)
            ->addFile($projectIml)
            ->addDirectory($inspectionProfilesDirectory);

        $this->ideaDirectory->create($this->inspectorPath);
    }

    public function getResult(): object
    {
        return new Directory($this->ideaDirectory->getPath());
    }
}
