<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Views;

use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;

class Error implements DisplayInterface
{
    /**
     * @var \Throwable
     */
    private $throwable;

    /**
     * @var bool
     */
    private $verbose;

    public function __construct(\Throwable $e, bool $verbose)
    {
        $this->throwable = $e;

        $this->verbose = $verbose;
    }

    protected function getHeadlineMessage(): string
    {
        switch (get_class($this->throwable)) {
            case ConfigurationException::class:
                return 'Failed to complete inspections because of a probable error in the configuration file.';

            case InspectionsProfileException::class:
                return 'Failed to complete inspections because of a probable error in the inspections profile.';

            default:
                return 'Failed to complete inspections because of an unexpected error.';
        }
    }

    protected function getBugReportMessage(): string
    {
        return "If you think you've discovered a problem with the travis-phpstorm-inspector project,\n"
            . "please provide some context and a full copy of the exceptions reported below to:\n"
            . "  https://github.com/dan-mathews/travis-phpstorm-inspector/issues/new";
    }

    protected function getVerbosePromptMessage(): string
    {
        return "Please add -v to your command and rerun to see more details.";
    }

    protected function getDetails(): string
    {
        return ($this->verbose) ? (string) $this->throwable : $this->throwable->getMessage();
    }

    public function display(): void
    {
        echo "\n" . $this->getHeadlineMessage() . "\n\n";
        echo ($this->verbose) ? $this->getBugReportMessage() : $this->getVerbosePromptMessage();
        echo "\n\n" . $this->getDetails() . "\n";
    }
}
