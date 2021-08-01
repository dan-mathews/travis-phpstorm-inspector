<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\ResultProcessing;

class Problems extends \SplHeap
{
    private const IGNORED_SEVERITIES = [
        'TYPO',
    ];

    /** @param array<mixed> $jsonProblems */
    public function addProblems(array $jsonProblems): void
    {
        /** @var array<string, mixed> $jsonProblem */
        foreach ($jsonProblems as $jsonProblem) {
            $problem = new Problem($jsonProblem);

            if (in_array($problem->getSeverity(), self::IGNORED_SEVERITIES, true)) {
                continue;
            }

            $this->insert($problem);
        }
    }

    public function problemsToReport(): bool
    {
        return !$this->isEmpty();
    }

    public function getInspectionMessage(): string
    {
        if (!$this->problemsToReport()) {
            return "No problems to report.\n";
        }

        $count = $this->count();

        $output = $count . " problems were found during phpStorm inspection.\n";

        $currentFilename = '';

        $this->top();

        for ($i = 0; $i < $count; $i++) {
            /** @var Problem $problem */
            $problem = $this->current();

            if ($problem->getFilename() !== $currentFilename) {
                $output .= "\nProblems in " . $problem->getFilename() . ":\n";
                $currentFilename = $problem->getFilename();
            }

            $output .= "  line " . str_pad($problem->getLine(), 3) . '  ' . str_pad($problem->getSeverity(), 13) . ' ('
            . $problem->getProblemName() . '): ' . $problem->getDescription() . "\n";

            $this->next();
        }

        return $output;
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

        $descriptionComparison = strcmp(strtoupper($value2->getDescription()), strtoupper($value1->getDescription()));

        return $descriptionComparison;
    }
}
