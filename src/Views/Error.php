<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Views;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Exceptions\AbstractAppException;
use TravisPhpstormInspector\Output\OutputStyler;

class Error implements DisplayInterface
{
    /**
     * @var \Throwable
     */
    private $throwable;

    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(\Throwable $e, OutputInterface $output)
    {
        $this->throwable = $e;

        $this->output = $output;
    }

    protected function getHeadlineMessage(): string
    {
        if (is_a($this->throwable, AbstractAppException::class)) {
            return $this->throwable->getHeadlineMessage();
        }

        return OutputStyler::warn('Failed to complete inspections because of an unexpected error.');
    }

    public function display(): void
    {
        $this->output->writeln('');
        $this->output->writeln($this->getHeadlineMessage());
        $this->output->writeln('');

        if ($this->output->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $this->output->writeln('Please add -v to your command and rerun to see more details.');
            $this->output->writeln('');
            $this->output->writeln($this->throwable->getMessage());
            return;
        }

        $this->output->writeln('If you think you\'ve discovered a problem with the travis-phpstorm-inspector project');
        $this->output->writeln('please provide some context and a full copy of the exceptions reported below to:');
        $this->output->writeln('  https://github.com/dan-mathews/travis-phpstorm-inspector/issues/new');
        $this->output->writeln('');
        $this->output->writeln((string) $this->throwable);
    }
}
