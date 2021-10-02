<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use TravisPhpstormInspector\Commands\InspectCommand;
use TravisPhpstormInspector\IdeaDirectory\Directories\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfilesDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ModulesXml;
use TravisPhpstormInspector\IdeaDirectory\Files\PhpXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProfileSettingsXml;
use TravisPhpstormInspector\IdeaDirectory\Files\ProjectIml;

/**
 * @implements BuilderInterface<IdeaDirectory>
 */
class IdeaDirectoryBuilder implements BuilderInterface
{
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
     * @var IdeaDirectory
     */
    private $ideaDirectory;

    public function __construct(
        string $inspectorPath,
        InspectionsXml $inspectionsXml,
        string $phpVersion
    ) {
        $this->inspectorPath = $inspectorPath;
        $this->inspectionsXml = $inspectionsXml;
        $this->phpVersion = $phpVersion;
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function build(): void
    {
        $profileSettingsXml = new ProfileSettingsXml($this->inspectionsXml->getProfileNameValue());

        $inspectionProfilesDirectory = new InspectionProfilesDirectory(
            $profileSettingsXml,
            $this->inspectionsXml
        );

        //TODO use the real project name from location in ModulesXml and ProjectIml
        $modulesXml = new ModulesXml();
        $phpXml = new PhpXml($this->phpVersion);
        $projectIml = new ProjectIml(InspectCommand::NAME);

        $this->ideaDirectory = new IdeaDirectory(
            $modulesXml,
            $phpXml,
            $projectIml,
            $inspectionProfilesDirectory
        );

        $this->ideaDirectory->create($this->inspectorPath);
    }

    public function getResult(): object
    {
        return $this->ideaDirectory;
    }
}
