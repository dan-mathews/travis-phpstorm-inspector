<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\Exceptions\ConfigurationException;

class Configuration
{
    private const DEFAULT_DOCKER_REPOSITORY = 'danmathews1/phpstorm';
    private const DEFAULT_DOCKER_TAG = 'latest';
    private const DEFAULT_INSPECTION_PROFILE_PATH = '/data/default.xml';
    private const DEFAULT_VERBOSE = false;
    private const DEFAULT_IGNORED_SEVERITIES = [];

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
    private $ignoredSeverities;

    /**
     * @var string
     */
    private $dockerRepository;

    /**
     * @var string
     */
    private $dockerTag;

    /**
     * @var string
     */
    private $appRootPath;

    /**
     * @var bool
     */
    private $verbose;

    /**
     * @var string
     */
    private $projectPath;

    /**
     * @var string
     */
    private $inspectionProfile;

    /**
     * @param array<mixed> $ignoredSeverities
     * @param string|null $dockerRepository
     * @param string|null $dockerTag
     * @param string $appRootPath
     * @param bool|null $verbose
     * @param string $projectPath
     * @param string|null $inspectionProfile
     * @throws ConfigurationException
     */
    public function __construct(
        ?array $ignoredSeverities,
        ?string $dockerRepository,
        ?string $dockerTag,
        string $appRootPath,
        ?bool $verbose,
        string $projectPath,
        ?string $inspectionProfile
    ) {
        $this->ignoredSeverities = ($ignoredSeverities !== null)
            ? $this->validateIgnoredSeverities($ignoredSeverities)
            : self::DEFAULT_IGNORED_SEVERITIES;

        $this->dockerRepository = $dockerRepository ?? self::DEFAULT_DOCKER_REPOSITORY;

        $this->dockerTag = $dockerTag ?? self::DEFAULT_DOCKER_TAG;

        $this->appRootPath = $appRootPath;

        $this->verbose = $verbose ?? self::DEFAULT_VERBOSE;

        $this->projectPath = $projectPath;

        $this->inspectionProfile = $inspectionProfile ?? $this->getAppRootPath()
            . self::DEFAULT_INSPECTION_PROFILE_PATH;
    }

    /**
     * @param array<mixed> $ignoredSeverities
     * @throws ConfigurationException
     * @psalm-suppress MixedPropertyTypeCoercion - we validate $ignoredSeverities is string[], throwing after array_diff
     */
    private function validateIgnoredSeverities(array $ignoredSeverities): array
    {
        if ([] !== array_diff($ignoredSeverities, self::VALID_IGNORED_SEVERITIES)) {
            throw new ConfigurationException(
                'Invalid values for ignored severities. The allowed values are: '
                . implode(', ', self::VALID_IGNORED_SEVERITIES) . '.'
            );
        }

        return $ignoredSeverities;
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

    public function getAppRootPath(): string
    {
        return $this->appRootPath;
    }

    public function getProjectPath(): string
    {
        return $this->projectPath;
    }

    public function getVerbose(): bool
    {
        return $this->verbose;
    }

    public function getInspectionProfile(): string
    {
        return $this->inspectionProfile;
    }
}
