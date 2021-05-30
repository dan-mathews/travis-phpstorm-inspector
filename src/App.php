<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class App
{
    public const RESULTS_DIR_NAME = 'inspectionResults';

    /**
     * @var string
     */
    private $projectRoot;

    /**
     * @var string
     */
    private $resultsDirPath;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;

        $this->resultsDirPath = $projectRoot . '/' . self::RESULTS_DIR_NAME;
    }

    public function run(): void
    {
        $command = "PhpStorm/bin/phpstorm.sh inspect $this->projectRoot $this->projectRoot/.idea/inspectionProfiles/exampleStandards.xml $this->resultsDirPath -changes -format json -v2";

        echo 'Running command: ' . $command . "/n";

        passthru($command);

        $resultsProcessor = new ResultsProcessor();

        $resultsProcessor->process($this->resultsDirPath);
    }
}