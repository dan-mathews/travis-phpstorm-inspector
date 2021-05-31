<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\Directories\Idea;
use TravisPhpstormInspector\IdeaDirectory\SimpleIdeaFactory;

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

        $projectRootInfo = new \SplFileInfo($projectRoot);

        $this->projectRoot = $projectRootInfo->getRealPath();

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

        passthru($command);

        $resultsProcessor = new ResultsProcessor($this->projectRoot);

        $resultsProcessor->process();
    }
}
