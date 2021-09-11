<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Directories\IdeaDirectory;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;

class InspectionCommand
{
    /**
     * @var ProjectDirectory
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
        ProjectDirectory $project,
        IdeaDirectory $ideaDirectory,
        ResultsDirectory $resultsDirectory,
        DockerImage $dockerImage,
        bool $verbose
    ) {
        $this->projectDirectory = $project;

        /** @noinspection UnusedConstructorDependenciesInspection this is not dead code, it's a dependency of the class
         * because experience shows the inspection command doesn't run properly without a valid idea directory.
         */
        $this->ideaDirectory = $ideaDirectory;

        $this->resultsDirectory = $resultsDirectory;

        $this->inspectionsXml = $this->ideaDirectory->getInspectionsXml();

        $this->dockerImage = $dockerImage;

        $this->verbose = $verbose;
    }

    private function mountCommand(string $source, string $target, bool $readOnly = false): string
    {
        // As we're mounting their whole project into /app, and mounting our generated .idea directory into /app/.idea,
        // there is the potential to overwrite their .idea directory locally if we're not careful.
        // Here we explicitly state private bind-propagation to prevent this possibility.
        return '--mount '
            . 'type=bind'
            . ',source=' . $source
            . ',target=' . $target
            . ',bind-propagation=private'
            . ($readOnly ? ',readonly' : '');
    }

    /**
     * @throws \RuntimeException
     */
    public function run(): void
    {
        $command = implode(' ', [
            'docker run ',
            //TODO place the results directory outside of their project so this can be readonly
            $this->mountCommand($this->projectDirectory->getPath(), '/app'),
            // this must not be readonly as phpstorm attempts to create file '/app/.idea/.gitignore'
            $this->mountCommand($this->ideaDirectory->getPath(), '/app/.idea', false),
            $this->dockerImage->getReference(),
            $this->getPhpstormCommand(),
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

    private function getPhpstormCommand(): string
    {
        return implode(' ', [
            '/bin/bash phpstorm.sh inspect',
            '/app',
            '/app/.idea/inspectionProfiles/' . $this->inspectionsXml->getName(),
            '/app/' . $this->resultsDirectory->getName(),
            '-changes',
            '-format json',
            '-v2',
        ]);
    }
}
