<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\DockerException;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
use TravisPhpstormInspector\ResultProcessing\Problems;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class Inspection
{
    /**
     * @var InspectionRunner
     */
    private $inspectionCommand;

    /**
     * @var ResultsProcessor
     */
    private $resultsProcessor;

    /**
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws DockerException
     */
    public function __construct(Configuration $configuration)
    {
        $ideaDirectoryBuilder = new IdeaDirectoryBuilder();

        $ideaDirectory = $ideaDirectoryBuilder->build(
            $configuration->getAppDirectory()->getPath(),
            $configuration->getInspectionProfile()
        );

        $dockerImage = new DockerFacade(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag()
        );

        $resultsDirectory = new ResultsDirectory();

        $resultsDirectory->create($configuration->getAppDirectory()->getPath());

        $this->inspectionCommand = new InspectionRunner(
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
     * @throws DockerException
     */
    public function run(): Problems
    {
        $this->inspectionCommand->run();

        return $this->resultsProcessor->process();
    }
}
