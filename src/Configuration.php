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
    private $ignoredSeverities = self::DEFAULT_IGNORED_SEVERITIES;

    /**
     * @var string
     */
    private $dockerRepository = self::DEFAULT_DOCKER_REPOSITORY;

    /**
     * @var string
     */
    private $dockerTag = self::DEFAULT_DOCKER_TAG;

    /**
     * @var Directory
     */
    private $appDirectory;

    /**
     * @var bool
     */
    private $verbose = self::DEFAULT_VERBOSE;

    /**
     * @var Directory
     */
    private $projectDirectory;

    /**
     * @var File
     */
    private $inspectionProfile;

    /**
     * @param string $appRootPath
     * @param string $projectPath
     */
    public function __construct(
        string $projectPath,
        string $appRootPath
    ) {
        //todo try catch
        $this->projectDirectory = new Directory($projectPath);

        //todo try catch
        $this->appDirectory = new Directory($appRootPath);

        //todo make xml file class?
        $this->inspectionProfile = new File($this->appDirectory->getPath() . self::DEFAULT_INSPECTION_PROFILE_PATH);
    }

    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @param array<mixed> $ignoredSeverities
     * @throws ConfigurationException
     * @psalm-suppress MixedPropertyTypeCoercion - we validate $ignoredSeverities is string[], throwing after array_diff
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

    public function getDockerRepository(): string
    {
        return $this->dockerRepository;
    }

    public function getDockerTag(): string
    {
        return $this->dockerTag;
    }

    public function getAppDirectory(): Directory
    {
        return $this->appDirectory;
    }

    public function getProjectDirectory(): Directory
    {
        return $this->projectDirectory;
    }

    public function getVerbose(): bool
    {
        return $this->verbose;
    }

    public function getInspectionProfile(): File
    {
        return $this->inspectionProfile;
    }

    public function setDockerRepository(string $dockerRepository): void
    {
        $this->dockerRepository = $dockerRepository;
    }

    public function setDockerTag(string $dockerTag): void
    {
        $this->dockerTag = $dockerTag;
    }

    public function setInspectionProfile(string $inspectionProfile): void
    {
        //todo try catch
        $this->inspectionProfile = new File($inspectionProfile);
    }
}
