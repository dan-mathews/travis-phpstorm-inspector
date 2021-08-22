<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;

class Configuration
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
     * @param array $ignoredSeverities
     * @throws ConfigurationException
     */
    public function setIgnoredSeverities(array $ignoredSeverities): void
    {
        if ([] !== array_diff($ignoredSeverities, self::VALID_IGNORED_SEVERITIES)) {
            throw new ConfigurationException(
                'Invalid values for ignored severities. The allowed values are: '
                . implode(', ', self::VALID_IGNORED_SEVERITIES) . '.'
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
