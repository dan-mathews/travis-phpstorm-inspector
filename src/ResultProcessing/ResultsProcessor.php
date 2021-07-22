<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\ResultProcessing;

use DirectoryIterator;
use TravisPhpstormInspector\ResultsDirectory;

class ResultsProcessor
{
    /**
     * @var DirectoryIterator
     */
    private $directory;

    public function __construct(ResultsDirectory $resultsDirectory)
    {
        try {
            $this->directory = new DirectoryIterator($resultsDirectory->getPath());
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                "Couldn't read inspection results directory " . $resultsDirectory->getName(),
                1,
                $e
            );
        }
    }

    public function process(): InspectionOutcome
    {
        $problems = new Problems();

        foreach ($this->directory as $fileInfo) {
            $fileName = $fileInfo->getFilename();

            if (
                '.descriptions.json' === $fileName ||
                // DuplicatedCode_aggregate.json is both formatted differently and
                // contains unnecessary detail beyond DuplicatedCode.json
                'DuplicatedCode_aggregate.json' === $fileName ||
                $fileInfo->isDot()
            ) {
                continue;
            }

            try {
                $file = $fileInfo->openFile();
            } catch (\Throwable $e) {
                throw new \RuntimeException(
                    "Could not open inspections results file '" . $fileName,
                    1,
                    $e
                );
            }

            $contents = $file->fread($file->getSize());

            if (false === $contents) {
                throw new \RuntimeException("Could not read inspections results file '" . $fileName);
            }

            try {
                /** @var null|array<string, mixed>|int|string */
                $jsonContents = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new \RuntimeException('Could not decode inspections results file as json: ' . $fileName, 1, $e);
            }

            if (
                !is_array($jsonContents) ||
                !isset($jsonContents['problems']) ||
                !is_array($jsonContents['problems'])
            ) {
                throw new \RuntimeException('No problems array within inspections file: ' . $fileName);
            }

            $problems->addProblems($jsonContents['problems']);
        }

        try {
            rmdir($this->directory->getPath());
        } catch (\Throwable $e) {
            echo 'Could not remove ' . ResultsDirectory::NAME . ' directory for clean up. Continuing anyway...';
        }

        if (!$problems->problemsToReport()) {
            return new InspectionOutcome(0, $problems->getInspectionMessage());
        }

        return new InspectionOutcome(1, $problems->getInspectionMessage());
    }
}
