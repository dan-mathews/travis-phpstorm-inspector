<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Views\Fail;
use TravisPhpstormInspector\Views\Error;
use TravisPhpstormInspector\Views\Pass;

class App
{
    public const NAME = 'travis-phpstorm-inspector';

    /**
     * @var Inspection
     */
    private $inspection;

    /**
     * @var ConfigurationBuilder
     */
    private $configurationBuilder;

    public function __construct()
    {
        try {
            $arguments = $_SERVER['argv'];

            $appRootPath = __DIR__ . '/../';

            $workingDirectory = $this->getWorkingDirectory();

            $this->configurationBuilder = new ConfigurationBuilder($arguments, $appRootPath, $workingDirectory);

            $configuration = $this->configurationBuilder->build();

            $this->inspection = new Inspection($configuration);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    public function run(): void
    {
        try {
            $problems = $this->inspection->run();
        } catch (\Throwable $e) {
            $this->handleError($e);
        }

        if (!$problems->isEmpty()) {
            $view = new Fail($problems);

            $view->display();

            exit(1);
        }

        $view = new Pass();

        $view->display();
    }

    private function handleError(\Throwable $e): void
    {
        $verbose = (null !== $this->configurationBuilder)
            ? $this->configurationBuilder->getConfiguration()->getVerbose()
            : true;

        $view = new Error($e, $verbose);

        $view->display();

        exit(1);
    }

    /**
     * @throws \RuntimeException
     */
    private function getWorkingDirectory(): string
    {
        $workingDirectory = getcwd();

        if (false === $workingDirectory) {
            throw new \RuntimeException('Could not establish current working directory. Does the current, or any parent'
                . ' directory, not have the readable or search mode set?');
        }

        return $workingDirectory;
    }
}
