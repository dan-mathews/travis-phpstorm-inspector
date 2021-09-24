<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\DockerException;
use TravisPhpstormInspector\IdeaDirectory\Directories\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfilesDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;

class InspectionRunner
{
    /**
     * @var Directory
     */
    private $projectDirectory;

    /**
     * @var IdeaDirectory
     */
    private $ideaDirectory;

    /**
     * @var ResultsDirectory
     */
    private $resultsDirectory;

    /**
     * @var InspectionsXml
     */
    private $inspectionsXml;

    /**
     * @var DockerFacade
     */
    private $dockerFacade;

    /**
     * @var bool
     */
    private $verbose;

    public function __construct(
        Directory $project,
        IdeaDirectory $ideaDirectory,
        InspectionsXml $inspectionsProfile,
        ResultsDirectory $resultsDirectory,
        DockerFacade $dockerFacade,
        bool $verbose
    ) {
        $this->projectDirectory = $project;

        $this->ideaDirectory = $ideaDirectory;

        $this->resultsDirectory = $resultsDirectory;

        $this->inspectionsXml = $inspectionsProfile;

        $this->dockerFacade = $dockerFacade;

        $this->verbose = $verbose;
    }

    // shouldn't chmod, should chown back to user? Or copy whole project to new location? Or run as $USER
    // --mount type=bind,source=/etc/passwd,target=/etc/passwd
    // --mount type=bind,source=/etc/group,target=/etc/group

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     * @throws DockerException
     */
    public function run(): void
    {
        // As we're mounting their whole project into /app, and mounting our generated .idea directory into /app/.idea,
        // there is the potential to overwrite their .idea directory locally if we're not careful.
        // The mounted directories can't be readonly (phpstorm modifies files such as /app/.idea/shelf/* and
        // /app/.idea/.gitignore) but we can explicitly state private bind-propagation to prevent overwriting.
        $this->dockerFacade
            ->mount($this->projectDirectory->getPath(), '/app')
            ->mount($this->ideaDirectory->getPath(), '/app/.idea')
            ->mount($this->resultsDirectory->getPath(), '/results')
            ->addCommand($this->getPhpstormCommand())
            ->addCommand($this->getChmodCommand())
            ->setTimeout(300);

        $code = $this->dockerFacade->run();

        if ($code !== 0) {
            throw new \RuntimeException("PhpStorm's Inspection command exited with a non-zero code.");
        }
    }

    /**
     * @param string[] $commands
     * @return string
     */
    private function getMultipleBashCommands(array $commands): string
    {
        return '/bin/bash -c "' . implode('; ', $commands) . '"';
    }

    private function getChmodCommand(): string
    {
        return 'chmod -R 777 /app/.idea';
    }

    private function getPhpstormCommand(): string
    {
        return implode(' ', [
            '/bin/bash phpstorm.sh inspect',
            '/app',
            '/app/.idea/' . InspectionProfilesDirectory::NAME . '/' . $this->inspectionsXml->getName(),
            '/results',
            '-changes',
            '-format json',
            '-v2',
        ]);
    }
}
