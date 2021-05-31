<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use DirectoryIterator;

class ResultsProcessor
{
    public const DIRECTORY_NAME = 'inspectionResults';

    /**
     * @var DirectoryIterator
     */
    private $directory;

    public function __construct(string $parentDirectoryPath)
    {
        try {
            $this->directory = new DirectoryIterator($parentDirectoryPath . '/' . self::DIRECTORY_NAME);
        } catch (\Throwable $e) {
            echo "Error: couldn't read inspection results directory (" . self::DIRECTORY_NAME . ")\n" . $e->getMessage() . "\n" . $e->getTraceAsString();

            exit(1);
        }
    }

    public function process(): void
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

                $contents = $file->fread($file->getSize());
            } catch (\Throwable $e) {
                echo "Error: Could not open and read inspections results file '" . $fileName . "'\n"
                    . $e->getMessage() . "\n" . $e->getTraceAsString();

                exit(1);
            }

            try {
                $jsonContents = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                echo "Error: Could not decode inspections results file '" . $fileName . "' as json\n"
                    . $e->getMessage() . "\n" . $e->getTraceAsString();

                exit(1);
            }

            if (!isset($jsonContents['problems'])) {
                echo "Error: No problems array within inspections file '" . $fileName . "'";

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
