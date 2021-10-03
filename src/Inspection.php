<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\ResultProcessing\Problems;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class Inspection
{
    private const DIRECTORY_NAME_RESULTS = 'travis-phpstorm-inspector-results';
    public const DIRECTORY_NAME_INSPECTION_PROFILES = 'inspectionProfiles';

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
     * @throws FilesystemException
     */
    public function __construct(Configuration $configuration)
    {
        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $configuration->getAppDirectory(),
            $configuration->getInspectionProfile(),
            $configuration->getPhpVersion()
        );

        $ideaDirectoryBuilder->build();
        $ideaDirectory = $ideaDirectoryBuilder->getResult();

        $dockerImage = new DockerImage(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag(),
            $configuration->getVerbose()
        );

        $resultsDirectory = $configuration->getAppDirectory()->createDirectory(self::DIRECTORY_NAME_RESULTS, true);

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
     * @throws \LogicException
     */
    public function run(): Problems
    {
        $this->inspectionCommand->run();

        return $this->resultsProcessor->process();
    }
}
