<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class CommandRunner
{
    /**
     * @var bool
     */
    private $verbose;

    public function __construct(bool $verbose = false)
    {
        $this->verbose = $verbose;
    }

    /**
     * @throws \RuntimeException
     */
    public function run(string $command): void
    {
        $output = [];

        $code = 1;

        if ($this->verbose) {
            passthru($command . ' 2>&1', $code);
        } else {
            exec($command . ' 2>&1', $output, $code);
        }

        if ($code !== 0) {
            /** @psalm-suppress MixedArgumentTypeCoercion - We know $output is fine for this purpose */
            throw new \RuntimeException(
                'Failure when running command.'
                // Don't re-print output if we already used passthru.
                . ($this->verbose ? '' : "\nOutput was: \n\t" . implode("\n\t", $output))
            );
        }
    }
}
