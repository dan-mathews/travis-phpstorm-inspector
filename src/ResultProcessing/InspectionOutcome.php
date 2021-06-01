<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\ResultProcessing;

class InspectionOutcome
{
    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var string
     */
    private $message;

    public function __construct(int $exitCode, string $message)
    {
        $this->exitCode = $exitCode;

        $this->message = $message;
    }

    public function getExitCode(): int
    {
        return $this->exitCode;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}