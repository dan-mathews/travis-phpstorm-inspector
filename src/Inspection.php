<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

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
     * @throws \InvalidArgumentException
     * @throws FilesystemException
     * @throws InspectionsProfileException
     * @throws DockerException
     */
    public function __construct(Configuration $configuration)
    {
        //todo make this a service
        $this->configuration = $configuration;
        $appDirectory = $configuration->getAppDirectory();
        $this->verbose = $configuration->getVerbose();

        $this->inspectionProfileXml = new InspectionProfileXml($configuration->getInspectionProfilePath());

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $appDirectory,
            $this->inspectionProfileXml,
            $configuration->getPhpVersion(),
            $configuration->getExcludeFolders()
        );

        $ideaDirectoryBuilder->build();
        $this->ideaDirectory = $ideaDirectoryBuilder->getResult();

        $this->dockerFacade = new DockerFacade(
            $configuration->getDockerRepository(),
            $configuration->getDockerTag()
        );

        //TODO create a cache directory to house the results, the phpstorm cache, and the copy of the local project.
        $this->resultsDirectory = $appDirectory->createDirectory(self::DIRECTORY_NAME_RESULTS, true);

        $this->resultsProcessor = new ResultsProcessor($this->resultsDirectory, $configuration);
    }

    /**
     * @return Problems
     * @throws \RuntimeException
     * @throws DockerException
     */
    public function run(): Problems
    {
        $commandRunner = new CommandRunner($this->verbose);

        //todo name each caommand to represent what it does
        //todo follow the ->addCommand() pattern from dockerFacade
        //todo make a cache class to keep all this logic and hold relevant dirs
        $command = 'rm -rf ' . $this->resultsDirectory->getPath() . '/../tmp/*';

        $commandRunner->run($command);

        //todo: strip these excludes back so they make sense in flashcard context. Solve self-analysis another time
        $command = 'rsync -a --exclude \'.idea\' --exclude \'cache\' --exclude \'tmp\' '
            . $this->configuration->getProjectDirectory()->getPath() . '/ ' . $this->resultsDirectory->getPath()
            . '/../tmp';

        $commandRunner->run($command);

        //TODO create a wrapper class for the exec command and add a comment to explain why symfony Process doesn't work
        // for this project. Then move this and the docker facade execs to that and remove symfony Process.

        $command = 'if [ ! -d "/home/$USER/.cache/travis-phpstorm-inspector" ]; '
            . 'then mkdir /home/$USER/.cache/travis-phpstorm-inspector; '
            . 'fi';

        $commandRunner->run($command);

        // As we're mounting their whole project into /app, and mounting our generated .idea directory into /app/.idea,
        // there is the potential to overwrite their .idea directory locally if we're not careful.
        // Apart from user directories, these can't be readonly (phpstorm modifies files such as /app/.idea/shelf/* and
        // /app/.idea/.gitignore) but we can explicitly state private bind-propagation to prevent overwriting.
        $this->dockerFacade
            ->mount($this->resultsDirectory->getPath() . '/../tmp', '/app')
            ->mount($this->ideaDirectory->getPath(), '/app/.idea')
            ->mount($this->resultsDirectory->getPath(), '/results')
            ->mount('/etc/group', '/etc/group', true)
            ->mount('/etc/passwd', '/etc/passwd', true)
            ->mount('/home/$USER/.cache/travis-phpstorm-inspector', '/home/$USER/.cache/JetBrains')
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
}
