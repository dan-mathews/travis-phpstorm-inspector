<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\SimpleIdeaFactory;
use TravisPhpstormInspector\ResultProcessing\InspectionOutcome;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class App
{
    public const NAME = 'travis-phpstorm-inspector';

    /**
     * @var ResultsDirectory
     */
    private $resultsDirectory;

    /**
     * @var InspectionCommand
     */
    private $inspectionCommand;

    /**
     * @var ResultsProcessor
     */
    private $resultsProcessor;

    public function __construct(string $projectPath, string $inspectionsXmlPath)
    {
        $project = new Project($projectPath);

        $this->resultsDirectory = new ResultsDirectory();

        $this->resultsDirectory->create($project->getPath());

        $simpleIdeaFactory = new SimpleIdeaFactory();

        $ideaDirectory = $simpleIdeaFactory->create($project, $inspectionsXmlPath);

        $this->inspectionCommand = new InspectionCommand($project, $ideaDirectory, $this->resultsDirectory);

        $this->resultsProcessor = new ResultsProcessor($this->resultsDirectory);
    }

    public function run(): InspectionOutcome
    {
        $this->inspectionCommand->run();

        return $this->resultsProcessor->process();
    }
}
