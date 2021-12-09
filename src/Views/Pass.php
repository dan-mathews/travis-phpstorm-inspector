<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Views;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Output\OutputStyler;

class Pass implements DisplayInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    public function display(): void
    {
        $this->output->writeln('');
        $this->output->writeln(OutputStyler::success('No problems to report.'));
    }
}
