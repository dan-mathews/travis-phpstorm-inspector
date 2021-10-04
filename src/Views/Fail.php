<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Views;

use TravisPhpstormInspector\ResultProcessing\Problem;
use TravisPhpstormInspector\ResultProcessing\Problems;

class Fail implements DisplayInterface
{
    /**
     * @var Problems
     */
    private $problems;

    public function __construct(Problems $problems)
    {
        $this->problems = $problems;
    }

    public function display(): void
    {
        $count = $this->problems->count();

        $output = $count . " problems were found during phpStorm inspection.\n";

        $currentFilename = '';

        $this->problems->top();

        for ($i = 0; $i < $count; $i++) {
            /** @var Problem $problem */
            $problem = $this->problems->current();

            if ($problem->getFilename() !== $currentFilename) {
                $output .= "\nProblems in " . $problem->getFilename() . ":\n";
                $currentFilename = $problem->getFilename();
            }

            $output .= '  line ' . str_pad($problem->getLine(), 3) . ' ' . str_pad($problem->getSeverity(), 13) . ' ('
                . $problem->getProblemName() . ') ' . $problem->getDescription() . "\n";

            $this->problems->next();
        }

        echo $output;
    }
}
