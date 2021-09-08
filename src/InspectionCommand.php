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

    /**
     * @throws \RuntimeException
     */
    public function run(): void
    {
        $verboseOutput = $this->verbose ? '' : ' 2>&1';

        $command = implode(' ', [
            'docker run',
            '-v ' . $this->projectDirectory->getPath() . ':/app',
            $this->dockerImage->getReference(),
            $this->getPhpstormCommand(),
        ]) . $verboseOutput;

        echo 'Running command: ' . $command . "\n";

        $code = 1;

        $output = [];

        exec($command, $output, $code);

        if ($code !== 0) {
            throw new \RuntimeException("PhpStorm's Inspection command exited with a non-zero code.");
        }
    }

    private function getPhpstormCommand(): string
    {
        return implode(' ', [
            'PhpStorm/bin/phpstorm.sh inspect',
            '/app',
            '/app/.idea/inspectionProfiles/' . $this->inspectionsXml->getName(),
            '/app/' . $this->resultsDirectory->getName(),
            '-changes',
            '-format json',
            '-v2',
        ]);
    }
}
