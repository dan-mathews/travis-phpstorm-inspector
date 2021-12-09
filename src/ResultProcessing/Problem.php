<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\ResultProcessing;

class Problem
{
    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $filename;

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

    /**
     * @var string
     */
    private $filenameLink;

    /**
     * @param array<string, mixed> $problem
     * @psalm-suppress MixedAssignment - we know from PhpStorm's result structure what types these will be
     */
    public function __construct(array $problem, string $projectDirectoryPath)
    {
        $this->filename = $this->getCleanFilename($problem);

        $this->filenameLink = 'file:///' . $projectDirectoryPath . '/' . $this->filename;

        $this->description = $problem['description'] ?? 'unknown description';

        $this->line = (string) ($problem['line'] ?? 'unknown line');

        $this->problemName = $problem['problem_class']['name'] ?? 'unknown problem';

        $this->severity = $problem['problem_class']['severity'] ?? 'unknown severity';
    }

    /** @param array<string, mixed> $problem */
    private function getCleanFilename(array $problem): string
    {
        if (empty($problem['file'])) {
            return 'unknown file';
        }

        /** @var string $filename */
        $filename = $problem['file'];

        if (strpos($filename, 'file://$PROJECT_DIR$/') === 0) {
            return substr($filename, 21);
        }

        return $filename;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getFilenameLink(): string
    {
        return $this->filenameLink;
    }

    public function getLine(): string
    {
        return $this->line;
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
