<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;

class CommandRunner
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @throws \RuntimeException
     */
    public function run(string $command): void
    {
        $output = [];

        $code = 1;

        $verbose = $this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;

        if ($verbose) {
            $this->output->writeln('Running command: ' . $command . ' 2>&1');

            passthru($command . ' 2>&1', $code);
        } else {
            exec($command . ' 2>&1', $output, $code);
        }

        if ($code !== 0) {
            /** @psalm-suppress MixedArgumentTypeCoercion - We know $output is fine for this purpose */
            throw new \RuntimeException(
                'Failure when running command.'
                // Don't re-print output if we already used passthru.
                . ($verbose ? '' : "\nOutput was: \n\t" . implode("\n\t", $output))
            );
        }
    }
}
