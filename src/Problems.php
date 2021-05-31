<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class Problems
{
    private const IGNORED_SEVERITIES = [
        'TYPO',
    ];

    /**
     * @var Problem[][]
     */
    private $files = [];

    public function addProblems(array $jsonProblems): void
    {
        foreach ($jsonProblems as $jsonProblem) {
            $filename = $this->getCleanFilename($jsonProblem);

            $problem = new Problem($jsonProblem);

            if (in_array($problem->getSeverity(), self::IGNORED_SEVERITIES, true)) {
                continue;
            }

            if (!isset($this->files[$filename])) {
                $this->files[$filename] = [];
            }

            $this->files[$filename][] = $problem;
        }
    }

    private function getCleanFilename(array $problem): string
    {
        if (empty($problem['file'])) {
            return 'unknown file';
        }

        if (strpos($problem['file'], 'file://$PROJECT_DIR$/') === 0) {
            return substr($problem['file'], 21);
        }

        return $problem['file'];
    }

    public function problemsToReport(): bool
    {
        return !empty($this->files);
    }

    public function display(): void
    {
        if (!$this->problemsToReport()) {
            return;
        }

        echo "\e[31mProblems found from phpStorm inspection:\e[39m\n";

        foreach ($this->files as $filename => $problems) {
            $count = count($problems);

            $plural = (1 === $count) ? '' : 's';

            echo $count . ' problem' . $plural . ' detected in ' . $filename . ":\n";

            //TODO sort problems by line number for easy editing
            foreach ($problems as $problem) {
                echo '  - ' . $problem->getSeverity() . ' (' . $problem->getProblemName() . '): '
                    . $problem->getDescription() . ' (line ' . $problem->getLine() . ")\n";
            }
        }
    }
}