<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;
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
     * @throws InspectionsProfileException
     */
    public function __construct(Configuration $configuration)
    {
        $appDirectory = $configuration->getAppDirectory();
        $verbose = $configuration->getVerbose();

        $inspectionProfileXml = new InspectionProfileXml($configuration->getInspectionProfilePath());

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $appDirectory,
            $inspectionProfileXml,
            $configuration->getPhpVersion(),
            $configuration->getExcludeFolders()
        );

        $ideaDirectoryBuilder->build();
        $ideaDirectory = $ideaDirectoryBuilder->getResult();

        $dockerImage = new DockerImage(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag(),
            $verbose
        );

        $resultsDirectory = $appDirectory->createDirectory(self::DIRECTORY_NAME_RESULTS, true);

        $this->inspectionCommand = new InspectionCommand(
            $configuration->getProjectDirectory(),
            $ideaDirectory,
            $inspectionProfileXml,
            $resultsDirectory,
            $dockerImage,
            $verbose,
            $configuration->getWholeProject()
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
