<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Builders\CacheDirectoryBuilder;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
use TravisPhpstormInspector\Exceptions\DockerException;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;
use TravisPhpstormInspector\ResultProcessing\Problems;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class Inspection
{
    /**
     * @var ResultsProcessor
     */
    private $resultsProcessor;

    /**
     * @var DockerFacade
     */
    private $dockerFacade;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var InspectionProfileXml
     */
    private $inspectionProfileXml;

    /**
     * @var Directory
     */
    private $cacheDirectory;

    /**
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     * @throws InspectionsProfileException
     * @throws DockerException
     * @throws \RuntimeException
     */
    public function __construct(Configuration $configuration, OutputInterface $output)
    {
        $this->configuration = $configuration;
        $this->inspectionProfileXml = new InspectionProfileXml($configuration->getInspectionProfilePath());

        $commandRunner = new CommandRunner($configuration->getVerbose());

        $cacheDirectoryBuilder = new CacheDirectoryBuilder(
            $configuration,
            $this->inspectionProfileXml,
            $output,
            $commandRunner
        );

        $cacheDirectoryBuilder->build();

        $this->cacheDirectory = $cacheDirectoryBuilder->getResult();

        $this->dockerFacade = new DockerFacade(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag(),
            $commandRunner
        );

        $this->resultsProcessor = new ResultsProcessor(
            $this->cacheDirectory->getSubDirectory(CacheDirectoryBuilder::DIRECTORY_RESULTS),
            $configuration
        );
    }

    /**
     * @return Problems
     * @throws \RuntimeException
     * @throws DockerException
     * @throws FilesystemException
     */
    public function run(): Problems
    {
        $projectCopyDirectory = $this->cacheDirectory->getSubDirectory(CacheDirectoryBuilder::DIRECTORY_PROJECT_COPY);
        $resultsDirectory = $this->cacheDirectory->getSubDirectory(CacheDirectoryBuilder::DIRECTORY_RESULTS);
        $ideaDirectory = $this->cacheDirectory->getSubDirectory(IdeaDirectoryBuilder::DIRECTORY_IDEA);
        $jetbrainsDirectory = $this->cacheDirectory->getSubDirectory(CacheDirectoryBuilder::DIRECTORY_JETBRAINS);

        // As we're mounting their whole project into /app, and mounting our generated .idea directory into /app/.idea,
        // there is the potential to overwrite their .idea directory locally if we're not careful.
        // Apart from user directories, these can't be readonly (phpstorm modifies files such as /app/.idea/shelf/* and
        // /app/.idea/.gitignore) but we can explicitly state private bind-propagation to prevent overwriting.
        $this->dockerFacade
            ->mount($projectCopyDirectory->getPath(), '/app')
            ->mount($ideaDirectory->getPath(), '/app/.idea')
            ->mount($resultsDirectory->getPath(), '/results')
            ->mount('/etc/group', '/etc/group', true)
            ->mount('/etc/passwd', '/etc/passwd', true)
            ->mount($jetbrainsDirectory->getPath(), '/home/$USER/.cache/JetBrains')
            ->addCommand('chown -R $USER:$USER /home/$USER')
            ->addCommand($this->getPhpstormCommand());

        $this->dockerFacade->run();

        return $this->resultsProcessor->process();
    }

    private function getPhpstormCommand(): string
    {
        return implode(' ', [
            'runuser -u $USER',
            '--', // A double-dash in a shell command signals the end of options and disables further option processing.
            '/bin/bash phpstorm.sh inspect',
            '/app',
            '/app/.idea/' . IdeaDirectoryBuilder::DIRECTORY_INSPECTION_PROFILES . '/'
            . $this->inspectionProfileXml->getName(),
            '/results',
            ($this->configuration->getWholeProject() ? '' : '-changes'),
            '-format json',
            '-v2',
        ]);
    }
}
