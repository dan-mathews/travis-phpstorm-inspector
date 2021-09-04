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
    public function __construct(string $projectPath, string $inspectionsXmlPath)
    {
        $projectDirectory = new ProjectDirectory($projectPath);

        $resultsDirectory = new ResultsDirectory();

        $resultsDirectory->create($projectDirectory->getPath());

        $configurationParser = new ConfigurationParser();

        $configuration = $configurationParser->parse($projectDirectory->getPath() . '/' . Configuration::FILENAME);

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder();

        $ideaDirectory = $ideaDirectoryBuilder->build($projectDirectory, $inspectionsXmlPath);

        $dockerImage = new DockerImage('danmathews1/phpstorm-images', '1.0.0-phpstorm2021.1.2-ea4.0.6.4');

        $this->inspectionCommand = new InspectionCommand(
            $projectDirectory,
            $ideaDirectory,
            $resultsDirectory,
            $dockerImage
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
