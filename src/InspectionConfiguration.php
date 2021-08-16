<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

class InspectionConfiguration
{
    public const FILENAME = 'travis-phpstorm-inspector.json';

    public const VALID_IGNORED_SEVERITIES = [
        'TYPO',
        'WEAK WARNING',
        'WARNING',
        'ERROR',
        'SERVER PROBLEM' //todo test this
    ];

    /**
     * @var string[]
     */
    private $ignoredSeverities = [];

    /**
     * @param string[] $ignoredSeverities
     * @throws \InvalidArgumentException
     */
    public function setIgnoredSeverities(array $ignoredSeverities): void
    {
        if ([] !== array_diff($ignoredSeverities, self::VALID_IGNORED_SEVERITIES)) {
            throw new \InvalidArgumentException(
                'Invalid values for ignored severities. The allowed values are: '
                . implode(', ', self::VALID_IGNORED_SEVERITIES)
            );
        }

        $this->ignoredSeverities = $ignoredSeverities;
    }

    /**
     * @return string[]
     */
    public function getIgnoredSeverities(): array
    {
        return $this->ignoredSeverities;
    }
}
