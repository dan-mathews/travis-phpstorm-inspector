<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Directories\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\Directories\InspectionProfilesDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;

class InspectionCommand
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
     * @var DockerImage
     */
    private $dockerImage;

    /**
     * @var bool
     */
    private $verbose;

    public function __construct(
        Directory $project,
        IdeaDirectory $ideaDirectory,
        InspectionsXml $inspectionsProfile,
        ResultsDirectory $resultsDirectory,
        DockerImage $dockerImage,
        bool $verbose
    ) {
        $this->projectDirectory = $project;

        $this->ideaDirectory = $ideaDirectory;

        $this->resultsDirectory = $resultsDirectory;

        $this->inspectionsXml = $inspectionsProfile;

        $this->dockerImage = $dockerImage;

        $this->verbose = $verbose;
    }

    private function mountCommand(string $source, string $target): string
    {
        // As we're mounting their whole project into /app, and mounting our generated .idea directory into /app/.idea,
        // there is the potential to overwrite their .idea directory locally if we're not careful.
        // The mounted directories can't be readonly (phpstorm modifies files such as /app/.idea/shelf/* and
        // /app/.idea/.gitignore) but we can explicitly state private bind-propagation to prevent overwriting.

        return '--mount '
            . 'type=bind'
            . ',source=' . $source
            . ',target=' . $target
            . ',bind-propagation=private';
    }

    /**
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function run(): void
    {
        $command = implode(' ', [
            'docker run ',
            $this->mountCommand($this->projectDirectory->getPath(), '/app'),
            $this->mountCommand($this->ideaDirectory->getPath(), '/app/.idea'),
            $this->mountCommand($this->resultsDirectory->getPath(), '/results'),
            $this->dockerImage->getReference(),
            $this->getMultipleBashCommands([$this->getPhpstormCommand(), $this->getChmodCommand()])
        ]);

        //todo replace with verbose-aware outputter
        echo 'Running command: ' . $command . "\n";

        $code = 1;

        $output = [];

        if ($this->verbose) {
            passthru($command, $code);
        } else {
            exec($command . ' 2>&1', $output, $code);
        }

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
