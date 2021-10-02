<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use PhpParser\Node\Scalar\MagicConst\Dir;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
use TravisPhpstormInspector\IdeaDirectory\CreatableDirectory;
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
        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $configuration->getAppDirectory()->getPath(),
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

        //todo clean this up. Either a helper method to create dir, returning Directory, or symfony
        $resultsDirectory = new CreatableDirectory('travis-phpstorm-inspector-results');
        $resultsDirectory->create($configuration->getAppDirectory()->getPath());
        $resultsDirectory = new Directory($resultsDirectory->getPath());

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
