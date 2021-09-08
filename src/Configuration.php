<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;

class Configuration
{
    public const FILENAME = 'travis-phpstorm-inspector.json';
    private const DEFAULT_DOCKER_REPOSITORY = 'danmathews1/phpstorm';
    private const DEFAULT_DOCKER_TAG = 'latest';

    public const VALID_IGNORED_SEVERITIES = [
        'TYPO',
        'WEAK WARNING',
        'WARNING',
        'ERROR',
        'SERVER PROBLEM',
        'INFORMATION',
    ];

    /**
     * @var string[]
     */
    private $ignoredSeverities = [];

    /**
     * @var string
     */
    private $dockerRepository;

    /**
     * @var string
     */
    private $dockerTag;

    /**
     * @var bool
     */
    private $overwriteIdeaDir;

    /**
     * @throws ConfigurationException
     */
    public function __construct(
        array $ignoredSeverities,
        ?string $dockerRepository,
        ?string $dockerTag,
        bool $overwriteIdeaDir
    ) {
        $this->setIgnoredSeverities($ignoredSeverities);
        $this->dockerRepository = $dockerRepository ?? self::DEFAULT_DOCKER_REPOSITORY;
        $this->dockerTag = $dockerTag ?? self::DEFAULT_DOCKER_TAG;
        $this->overwriteIdeaDir = $overwriteIdeaDir;
    }

    /**
     * @param array<string|int, mixed> $ignoredSeverities
     * @throws ConfigurationException
     * @psalm-suppress MixedPropertyTypeCoercion - we validate $ignoredSeverities is string[], throwing after array_diff
     */
    private function setIgnoredSeverities(array $ignoredSeverities): void
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

    public function getDockerRepository(): string
    {
        return $this->dockerRepository;
    }

    public function getDockerTag(): string
    {
        return $this->dockerTag;
    }

    public function getOverwriteIdeaDir(): bool
    {
        return $this->overwriteIdeaDir;
    }
}
