<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;
use TravisPhpstormInspector\IdeaDirectory\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\InspectionProfilesDirectory;

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

        if (false === $useExistingIdeaDirectory) {
            $inspectionsXml = new InspectionsXml();

            $inspectionsXml->setContentsFromPath($inspectionsXmlPath);

            $inspectionProfilesDirectory = new InspectionProfilesDirectory();

            $inspectionProfilesDirectory->addFile($inspectionsXml);

            $ideaDirectory = new IdeaDirectory();

            $ideaDirectory->addDirectory($inspectionProfilesDirectory);

            $ideaDirectory->create($this->projectRoot);

            $this->inspectionsXmlPath = $this->projectRoot . '/' . IdeaDirectory::DIRECTORY_NAME . '/' . InspectionProfilesDirectory::DIRECTORY_NAME . '/' . $inspectionsXml->getName();
        }
    }

    public function run(): void
    {
        $command = "PhpStorm/bin/phpstorm.sh inspect $this->projectRoot $this->inspectionsXmlPath $this->resultsDirectoryPath -changes -format json -v2";

        echo 'Running command: ' . $command . "/n";

        passthru($command);

        $resultsProcessor = new ResultsProcessor();

        $resultsProcessor->process($this->resultsDirectoryPath);
    }
}
