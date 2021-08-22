<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Configuration\ConfigurationParser;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\IdeaDirectory\SimpleIdeaFactory;
use TravisPhpstormInspector\ResultProcessing\Problems;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class Inspection
{
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

    /**
     * @throws ConfigurationException
     * @throws InspectionsProfileException
     */
    public function __construct(string $projectPath, string $inspectionsXmlPath)
    {
        $project = new Project($projectPath);

        $this->resultsDirectory = new ResultsDirectory();

        $this->resultsDirectory->create($project->getPath());

        $inspectionConfigurationParser = new ConfigurationParser($project);

        $inspectionConfiguration = $inspectionConfigurationParser->parse();

        $simpleIdeaFactory = new SimpleIdeaFactory();

        $ideaDirectory = $simpleIdeaFactory->create($project, $inspectionsXmlPath);

        $this->inspectionCommand = new InspectionCommand($project, $ideaDirectory, $this->resultsDirectory);

        $this->resultsProcessor = new ResultsProcessor($this->resultsDirectory, $inspectionConfiguration);
    }

    /**
     * @return Problems
     * @throws \RuntimeException
     */
    public function run(): Problems
    {
        $this->inspectionCommand->run();

        return $this->resultsProcessor->process();
    }
}
