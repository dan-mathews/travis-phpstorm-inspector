<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\ResultProcessing;

use TravisPhpstormInspector\Configuration;

class Problems extends \SplHeap
{
    /**
     * @var Configuration
     */
    private $inspectionConfiguration;

    public function __construct(Configuration $inspectionConfiguration)
    {
        $this->inspectionConfiguration = $inspectionConfiguration;
    }

    /** @param array $jsonProblems */
    public function addProblems(array $jsonProblems): void
    {
        /** @var array<string, mixed> $jsonProblem */
        foreach ($jsonProblems as $jsonProblem) {
            $problem = new Problem($jsonProblem);

            if (in_array($problem->getSeverity(), $this->inspectionConfiguration->getIgnoredSeverities(), true)) {
                continue;
            }

            $this->insert($problem);
        }
    }

    /**
     * @param Problem $value1
     * @param Problem $value2
     * @return int
     */
    protected function compare($value1, $value2): int
    {
        $filenameComparison = strcmp($value2->getFilename(), $value1->getFilename());

        if ($filenameComparison !== 0) {
            return $filenameComparison;
        }

        $lineComparison =  $value2->getLine() <=> $value1->getLine();

        if ($lineComparison !== 0) {
            return $lineComparison;
        }

        $severityComparison = strcmp($value2->getSeverity(), $value1->getSeverity());

        if ($severityComparison !== 0) {
            return $severityComparison;
        }

        $problemComparison = strcmp(strtoupper($value2->getProblemName()), strtoupper($value1->getProblemName()));

        if ($problemComparison !== 0) {
            return $problemComparison;
        }

        return strcmp(strtoupper($value2->getDescription()), strtoupper($value1->getDescription()));
    }
}
