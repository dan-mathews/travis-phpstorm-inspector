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
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(Configuration $configuration)
    {
        $ideaDirectoryBuilder = new IdeaDirectoryBuilder();

        $ideaDirectory = $ideaDirectoryBuilder->build(
            $configuration->getAppDirectory()->getPath(),
            $configuration->getInspectionProfile()
        );

        $dockerImage = new DockerImage(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag(),
            $configuration->getVerbose()
        );

        $resultsDirectory = new ResultsDirectory();

        $resultsDirectory->create($configuration->getAppDirectory()->getPath());

        $this->inspectionCommand = new InspectionCommand(
            $configuration->getProjectDirectory(),
            $ideaDirectory,
            $configuration->getInspectionProfile(),
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
