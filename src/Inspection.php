<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
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
    public function __construct(Configuration $configuration)
    {
        $projectDirectory = new ProjectDirectory($configuration->getProjectDirectory()->getPath());

        $resultsDirectory = new ResultsDirectory();

        $resultsDirectory->create($configuration->getAppDirectory()->getPath());

        // this should have configuration as a dependency as the paths etc. it requires must have been validated already
        $ideaDirectoryBuilder = new IdeaDirectoryBuilder();

        // remove args from here and have config passed in in constructor
        $ideaDirectory = $ideaDirectoryBuilder->build(
            $configuration->getAppDirectory()->getPath(),
            $configuration->getInspectionProfile()->getPath()
        );

        $dockerImage = new DockerImage(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag(),
            $configuration->getVerbose()
        );

        $this->inspectionCommand = new InspectionCommand(
            $projectDirectory,
            $ideaDirectory,
            $resultsDirectory,
            $dockerImage,
            $configuration->getVerbose()
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
