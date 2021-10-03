<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\ResultProcessing;

use DirectoryIterator;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Directory;

class ResultsProcessor
{
    /**
     * @var DirectoryIterator
     */
    private $directory;

    /**
     * @var Configuration
     */
    private $inspectionConfiguration;

    /**
     * @param Directory $resultsDirectory
     * @param Configuration $inspectionConfiguration
     * @throws \InvalidArgumentException
     */
    public function __construct(Directory $resultsDirectory, Configuration $inspectionConfiguration)
    {
        try {
            $this->directory = new DirectoryIterator($resultsDirectory->getPath());
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException(
                "Couldn't read inspection results directory",
                1,
                $e
            );
        }

        $this->inspectionConfiguration = $inspectionConfiguration;
    }

    /**
     * @return Problems
     * @throws \RuntimeException
     */
    public function process(): Problems
    {
        $problems = new Problems($this->inspectionConfiguration);

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

        return $problems;
    }
}
