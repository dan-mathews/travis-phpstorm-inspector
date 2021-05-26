<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class Problem
{
    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $line;

    /**
     * @var string
     */
    private $problemName;

    /**
     * @var string
     */
    private $severity;

    public function __construct(array $problem)
    {
        $this->description = $problem['description'] ?? 'unknown description';
        $this->line = $problem['line'] ?? 'unknown line';
        $this->problemName = $problem['problem_class']['name'] ?? 'unknown problem';
        $this->severity = $problem['problem_class']['severity'] ?? 'unknown severity';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getLine(): string
    {
        return (string) $this->line;
    }

    public function getProblemName(): string
    {
        return $this->problemName;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }
}