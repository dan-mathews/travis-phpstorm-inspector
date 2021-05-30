<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

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
        $projectRootInfo = new \SplFileInfo($projectRoot);

        $this->projectRoot = $projectRootInfo->getRealPath();

        $this->resultsDirectoryPath = $this->projectRoot . '/' . ResultsProcessor::DIRECTORY_NAME;

        $ideaDirectoryPath = $this->projectRoot . '/' . IdeaDirectory::DIRECTORY_NAME;

        if (!is_dir($ideaDirectoryPath)) {
            $ideaDirectory = new IdeaDirectory();

            $ideaDirectory->create($this->projectRoot);
        }

        $inspectionProfilesDirectoryPath = $ideaDirectoryPath . '/' . InspectionProfilesDirectory::DIRECTORY_NAME;

        if (!is_dir($inspectionProfilesDirectoryPath)) {
            $inspectionProfilesDirectory = new InspectionProfilesDirectory();

            $inspectionProfilesDirectory->create($ideaDirectoryPath);
        }

        $inspectionsXmlFile = new \SplFileInfo($inspectionsXmlPath);

        if (!$inspectionsXmlFile->isReadable()) {
            echo 'Could not read the inspections profile at ' . $inspectionsXmlPath;
            exit(1);
        }

        if ('xml' !== $inspectionsXmlFile->getExtension()) {
            echo 'The inspections profile at ' . $inspectionsXmlPath . ' does not have an xml extension';
            exit(1);
        }

        //PhpStorm runs better when the inspections xml is within the idea directory
        $idealInspectionsXmlPath = $inspectionProfilesDirectoryPath . '/' . $inspectionsXmlFile->getFilename();

        if ($inspectionsXmlFile->getRealPath() !== $idealInspectionsXmlPath) {
            if (!is_file($inspectionProfilesDirectoryPath . '/' . $inspectionsXmlFile->getFilename())){
                echo "Copying the inspection profile to the project's " . IdeaDirectory::DIRECTORY_NAME . ' directory.';

                passthru('cp ' . $inspectionsXmlFile->getRealPath() . ' ' . $idealInspectionsXmlPath);
            } else {
                echo 'Using the inspection profile of the same name which already exists in the '
                . IdeaDirectory::DIRECTORY_NAME . " directory (this makes the inspections more reliable).\n";
            }
        }

        $this->inspectionsXmlPath = $idealInspectionsXmlPath;
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
