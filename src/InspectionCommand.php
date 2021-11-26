<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\DockerException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;

class InspectionCommand
{
    /**
     * @var Directory
     */
    private $projectDirectory;

    /**
     * @var Directory
     */
    private $ideaDirectory;

    /**
     * @var Directory
     */
    private $resultsDirectory;

    /**
     * @var InspectionProfileXml
     */
    private $inspectionProfileXml;

    /**
     * @var DockerFacade
     */
    private $dockerFacade;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var bool
     */
    private $wholeProject;

    public function __construct(
        Directory $project,
        Directory $ideaDirectory,
        InspectionProfileXml $inspectionProfileXml,
        Directory $resultsDirectory,
        DockerFacade $dockerFacade,
        bool $verbose,
        bool $wholeProject
    ) {
        $this->projectDirectory = $project;

        $this->ideaDirectory = $ideaDirectory;

        $this->resultsDirectory = $resultsDirectory;

        $this->inspectionProfileXml = $inspectionProfileXml;

        $this->dockerFacade = $dockerFacade;

        $this->verbose = $verbose;

        $this->wholeProject = $wholeProject;
    }

    /**
     * @throws DockerException
     */
    public function run(): void
    {
        exec('rm -rf ' . $this->resultsDirectory->getPath() . '/../tmp/*');
        //todo: strip these excludes back so they make sense in flashcard context. Solve self-analysis another time
        $copy = 'rsync -a --exclude \'.idea\' --exclude \'cache\' --exclude \'tmp\' '
            . $this->projectDirectory->getPath() . '/ ' . $this->resultsDirectory->getPath() . '/../tmp';

        exec($copy);

        //TODO create a wrapper class for the exec command and add a comment to explain why symfony Process doesn't work
        // for this project. Then move this and the docker facade execs to that and remove symfony Process.
        $output = [];
        $code = 1;
        exec(
            'if [ ! -d "/home/$USER/.cache/travis-phpstorm-inspector" ]; '
            . 'then mkdir /home/$USER/.cache/travis-phpstorm-inspector; '
            . 'fi 2>&1',
            $output,
            $code
        );

        if (0 !== $code) {
            throw new DockerException(implode("\n", $output));
        }

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
    }

    private function getPhpstormCommand(): string
    {
        return implode(' ', [
            'runuser -u $USER',
            '--', // A double-dash in a shell command signals the end of options and disables further option processing.
            '/bin/bash phpstorm.sh inspect',
            '/app',
            '/app/.idea/' . Inspection::DIRECTORY_NAME_INSPECTION_PROFILES . '/'
                . $this->inspectionProfileXml->getName(),
            '/results',
            ($this->wholeProject ? '' : '-changes'),
            '-format json',
            '-v2',
        ]);
    }
}
