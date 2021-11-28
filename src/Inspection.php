<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Builders\IdeaDirectoryBuilder;
use TravisPhpstormInspector\Exceptions\DockerException;
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
     * @var ResultsProcessor
     */
    private $resultsProcessor;

    /**
     * @var DockerFacade
     */
    private $dockerFacade;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var Directory
     */
    private $resultsDirectory;
    /**
     * @var mixed|Directory
     */
    private $ideaDirectory;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var InspectionProfileXml
     */
    private $inspectionProfileXml;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Directory
     */
    private $cacheDirectory;

    /**
     * @var Directory
     */
    private $jetBrainsDirectory;

    /**
     * @var Directory
     */
    private $projectCopyDirectory;

    /**
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     * @throws InspectionsProfileException
     * @throws DockerException
     * @throws \RuntimeException
     */
    public function __construct(Configuration $configuration, OutputInterface $output)
    {
        //todo make this a service
        $this->configuration = $configuration;
        $this->output = $output;
        $this->verbose = $configuration->getVerbose();
        $commandRunner = new CommandRunner($this->verbose);

        $this->cacheDirectory = $this->createCacheDirectory();

        $this->jetBrainsDirectory = $this->cacheDirectory->getOrCreateSubDirectory('JetBrains');
        $this->projectCopyDirectory = $this->cacheDirectory->getOrCreateSubDirectory('projectCopy');
        $this->projectCopyDirectory->empty();
        //TODO create a cache directory to house the results, the phpstorm cache, and the copy of the local project.
        $this->resultsDirectory = $this->cacheDirectory->getOrCreateSubDirectory(self::DIRECTORY_NAME_RESULTS);
        $this->resultsDirectory->empty();
        $this->configuration->getProjectDirectory()->copyTo($this->projectCopyDirectory, ['.idea'], $commandRunner);

        $this->inspectionProfileXml = new InspectionProfileXml($configuration->getInspectionProfilePath());

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $this->cacheDirectory,
            $this->inspectionProfileXml,
            $configuration->getPhpVersion(),
            $configuration->getExcludeFolders()
        );

        $ideaDirectoryBuilder->build();
        $this->ideaDirectory = $ideaDirectoryBuilder->getResult();

        $this->dockerFacade = new DockerFacade(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag(),
            $commandRunner
        );

        $this->resultsProcessor = new ResultsProcessor($this->resultsDirectory, $configuration);
    }

    /**
     * @return Problems
     * @throws \RuntimeException
     * @throws DockerException
     */
    public function run(): Problems
    {
        // As we're mounting their whole project into /app, and mounting our generated .idea directory into /app/.idea,
        // there is the potential to overwrite their .idea directory locally if we're not careful.
        // Apart from user directories, these can't be readonly (phpstorm modifies files such as /app/.idea/shelf/* and
        // /app/.idea/.gitignore) but we can explicitly state private bind-propagation to prevent overwriting.
        $this->dockerFacade
            ->mount($this->projectCopyDirectory->getPath(), '/app')
            ->mount($this->ideaDirectory->getPath(), '/app/.idea')
            ->mount($this->resultsDirectory->getPath(), '/results')
            ->mount('/etc/group', '/etc/group', true)
            ->mount('/etc/passwd', '/etc/passwd', true)
            ->mount($this->jetBrainsDirectory->getPath(), '/home/$USER/.cache/JetBrains')
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
            '/app/.idea/' . self::DIRECTORY_NAME_INSPECTION_PROFILES . '/'
            . $this->inspectionProfileXml->getName(),
            '/results',
            ($this->configuration->getWholeProject() ? '' : '-changes'),
            '-format json',
            '-v2',
        ]);
    }

    /**
     * @throws FilesystemException
     * @throws \RuntimeException
     */
    private function createCacheDirectory(): Directory
    {
        $userId = posix_geteuid();
        $userInfo = posix_getpwuid($userId);

        if (false === $userInfo) {
            throw new \RuntimeException('Could not retrieve user information, needed to create cache directory');
        }

        $user = $userInfo['name'];

        $cachePath = "/home/$user/.cache/travis-phpstorm-inspector";

        return new Directory($cachePath, $this->output, true);
    }
}
