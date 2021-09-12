<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Configuration\ConfigurationParser;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\IdeaDirectory\IdeaDirectoryBuilder;
use TravisPhpstormInspector\ResultProcessing\Problems;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class Inspection
{
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
    public function __construct(string $projectPath, string $inspectionsXmlPath, bool $verbose, string $inspectorPath)
    {
        $projectDirectory = new ProjectDirectory($projectPath);

        $resultsDirectory = new ResultsDirectory();

        $resultsDirectory->create($inspectorPath);

        $configurationParser = new ConfigurationParser();

        $configuration = $configurationParser->parse($projectDirectory->getPath() . '/' . Configuration::FILENAME);

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder();

        $ideaDirectory = $ideaDirectoryBuilder->build($inspectorPath, $inspectionsXmlPath);

        $dockerImage = new DockerImage($configuration, $verbose);

        $this->inspectionCommand = new InspectionCommand(
            $projectDirectory,
            $ideaDirectory,
            $resultsDirectory,
            $dockerImage,
            $verbose
        );

        $this->resultsProcessor = new ResultsProcessor($resultsDirectory, $configuration);
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
