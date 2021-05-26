<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use DirectoryIterator;

class ResultsProcessor
{
    public function process(string $directoryName): void
    {
        try {
            $directory = new DirectoryIterator($directoryName);
        } catch (\Throwable $e) {
            echo "Error: couldn't read inspection results directory\n" . $e->getMessage() . "\n" . $e->getTraceAsString();

            exit(1);
        }

        $problems = new Problems();

        foreach ($directory as $fileInfo) {
            if (
                $fileInfo->isDot() ||
                '.descriptions.json' === $fileInfo->getFilename()
            ) {
                continue;
            }

            try {
                $file = $fileInfo->openFile();

                $contents = $file->fread($file->getSize());
            } catch (\Throwable $e) {
                echo "Error: Could not open and read inspections results file '" . $fileInfo->getFilename() . "'\n"
                    . $e->getMessage() . "\n" . $e->getTraceAsString();

                exit(1);
            }

            try {
                $jsonContents = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                echo "Error: Could not decode inspections results file '" . $fileInfo->getFilename() . "' as json\n"
                    . $e->getMessage() . "\n" . $e->getTraceAsString();

                exit(1);
            }

            if (!isset($jsonContents['problems'])) {
                echo "Error: No problems array within inspections file '" . $fileInfo->getFilename() . "'";

                exit(1);
            }

            $problems->addProblems($jsonContents['problems']);
        }

        if (!$problems->problemsToReport()) {
            exit(0);
        }

        $problems->display();

        exit(1);
    }
}