<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Directories\Idea;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfiles;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;

class App
{
    public const NAME = 'travis-phpstorm-inspector';

    /**
     * @var string
     */
    private $inspectionsXmlPath;

    /**
     * @var string
     */
    private $projectRoot;

    /**
     * @var string
     */
    private $resultsDirectoryPath;

    public function __construct(string $projectRoot, string $inspectionsXmlPath)
    {
        //TODO make this configurable and throw for now if it's true
        $useExistingIdeaDirectory = false;

        $projectRootInfo = new \SplFileInfo($projectRoot);

        $this->projectRoot = $projectRootInfo->getRealPath();

        $this->resultsDirectoryPath = $this->projectRoot . '/' . ResultsProcessor::DIRECTORY_NAME;

        //TODO make a factory of some kind
        if (false === $useExistingIdeaDirectory) {
            $ideaDirectory = new Idea($this->projectRoot);

            $inspectionProfilesDirectory = new InspectionProfiles($ideaDirectory->getPath());

            $inspectionsXml = new InspectionsXml();

            $inspectionsXml->setContentsFromInspectionsXml($inspectionsXmlPath);

            $inspectionProfilesDirectory->addFile($inspectionsXml);

            $ideaDirectory->addDirectory($inspectionProfilesDirectory);

            $ideaDirectory->create();

            $this->inspectionsXmlPath = $inspectionProfilesDirectory->getPath() . '/' . $inspectionsXml->getName();
        }
    }

    public function run(): void
    {
        $command = "PhpStorm/bin/phpstorm.sh inspect $this->projectRoot $this->inspectionsXmlPath $this->resultsDirectoryPath -changes -format json -v2";

        echo 'Running command: ' . $command . "/n";

        passthru($command);

        $resultsProcessor = new ResultsProcessor($this->projectRoot);

        $resultsProcessor->process();
    }
}
