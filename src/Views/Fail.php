<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Views;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Output\OutputStyler;
use TravisPhpstormInspector\ResultProcessing\Problem;
use TravisPhpstormInspector\ResultProcessing\Problems;

class Fail implements DisplayInterface
{
    /**
     * @var Problems
     */
    private $problems;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(Problems $problems, OutputInterface $output)
    {
        $this->problems = $problems;

        $this->output = $output;
    }

    public function display(): void
    {
        $count = $this->problems->count();

        $this->output->writeln('');

        $this->output->writeln(OutputStyler::warn($count . ' problems were found during phpStorm inspection.'));

        $currentFilename = '';

        $this->problems->top();

        for ($i = 0; $i < $count; $i++) {
            /** @var Problem $problem */
            $problem = $this->problems->current();

            if ($problem->getFilename() !== $currentFilename) {
                $this->output->writeln('');
                $this->output->writeln('Problems in ' . $problem->getFilenameLink() . ':');
                $currentFilename = $problem->getFilename();
            }

            $this->output->writeln(
                '  line ' . str_pad($problem->getLine(), 4) . ' ' . str_pad($problem->getSeverity(), 13) . ' ('
                . $problem->getProblemName() . ') ' . $problem->getDescription()
            );

            $this->problems->next();
        }
    }
}
