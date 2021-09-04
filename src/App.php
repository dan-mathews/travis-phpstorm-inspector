<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Views\Fail;
use TravisPhpstormInspector\Views\Error;
use TravisPhpstormInspector\Views\Pass;

class App
{
    public const NAME = 'travis-phpstorm-inspector';

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var Inspection
     */
    private $inspection;

    public function __construct(string $projectPath, string $inspectionsXmlPath, bool $verbose = false)
    {
        $this->verbose = $verbose;

        try {
            $this->inspection = new Inspection($projectPath, $inspectionsXmlPath);
        } catch (\Throwable $e) {
            $view = new Error($e, $this->verbose);

            $view->display();

            exit(1);
        }
    }

    public function run(): void
    {
        try {
            $problems = $this->inspection->run();

            if ($problems->isEmpty()) {
                $view = new Pass();

                $exitCode = 0;
            } else {
                $view = new Fail($problems);

                $exitCode = 1;
            }
        } catch (\Throwable $e) {
            $view = new Error($e, $this->verbose);

            $exitCode = 1;
        }

        $view->display();

        exit($exitCode);
    }
}
