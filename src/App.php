<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Directories\Idea;
use TravisPhpstormInspector\IdeaDirectory\SimpleIdeaFactory;
use TravisPhpstormInspector\ResultProcessing\ResultsProcessor;

class App
{
    public const NAME = 'travis-phpstorm-inspector';

    /**
     * @var string
     */
    private $projectRoot;

    /**
     * @var string
     */
    private $resultsDirectoryPath;

    /**
     * @var Idea
     */
    private $ideaDirectory;

    public function __construct(string $projectRoot, string $inspectionsXmlPath)
    {
        //TODO make this configurable and throw for now if it's true
        $useExistingIdeaDirectory = false;

        $this->projectRoot = $this->validateProjectRoot($projectRoot);

        $this->resultsDirectoryPath = $this->projectRoot . '/' . ResultsProcessor::DIRECTORY_NAME;

        if (false !== $useExistingIdeaDirectory) {
            echo self::NAME . ' has not been built to work with existing ' . Idea::NAME . ' directories yet';
        }

        $simpleIdeaFactory = new SimpleIdeaFactory();

        $this->ideaDirectory = $simpleIdeaFactory->create($this->projectRoot, $inspectionsXmlPath);
    }

    public function run(): void
    {
        $command = "PhpStorm/bin/phpstorm.sh inspect $this->projectRoot " . $this->ideaDirectory->getInspectionsXmlPath() . " $this->resultsDirectoryPath -changes -format json -v2";

        echo 'Running command: ' . $command . "/n";

        $code = 1;

        passthru($command, $code);

        if ($code !== 0) {
            throw new \RuntimeException("PhpStorm's Inspection command exited with a non-zero code.");
        }

        $resultsProcessor = new ResultsProcessor($this->projectRoot);

        $inspectionOutcome = $resultsProcessor->process();

        echo $inspectionOutcome->getMessage();

        exit($inspectionOutcome->getExitCode());
    }

    private function validateProjectRoot(string $projectRoot): string
    {
        $fullPath = realpath($projectRoot);

        if (false === $fullPath) {
            throw new \InvalidArgumentException(
                'The given project root (' . $projectRoot . ') cannot be opened, or does not exist.'
            );
        }

        if (!is_dir($fullPath)) {
            throw new \InvalidArgumentException('The resolved project root (' . $fullPath . ') is not a directory.');
        }

        return $fullPath;
    }
}
