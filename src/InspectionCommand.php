<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Directories\Idea;
use TravisPhpstormInspector\IdeaDirectory\Files\InspectionsXml;

class InspectionCommand
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var Idea
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

    public function __construct(Project $project, Idea $ideaDirectory, ResultsDirectory $resultsDirectory)
    {
        $this->project = $project;

        /** @noinspection UnusedConstructorDependenciesInspection this is not dead code, it's a dependency of the class
         * because experience shows the inspection command doesn't run properly without a valid idea directory.
         */
        $this->ideaDirectory = $ideaDirectory;

        $this->resultsDirectory = $resultsDirectory;

        $this->inspectionsXml = $this->ideaDirectory->getInspectionsXml();
    }

    /**
     * @throws \RuntimeException
     */
    public function run(): void
    {
        $command = 'PhpStorm/bin/phpstorm.sh inspect ' . $this->project->getPath()
        . ' ' . $this->inspectionsXml->getPath() . ' ' . $this->resultsDirectory->getPath()
        . ' -changes -format json -v2';

        echo 'Running command: ' . $command . "\n";

        $code = 1;

        passthru($command, $code);

        if ($code !== 0) {
            throw new \RuntimeException("PhpStorm's Inspection command exited with a non-zero code.");
        }
    }
}
