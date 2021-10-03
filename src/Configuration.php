<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\FileContents\InspectionsXml;

class Configuration
{
    public const DEFAULT_DOCKER_REPOSITORY = 'danmathews1/phpstorm';
    public const DEFAULT_DOCKER_TAG = 'latest';
    public const DEFAULT_IGNORED_SEVERITIES = [];
    public const DEFAULT_INSPECTION_PROFILE_PATH = '/data/default.xml';
    public const DEFAULT_PHP_VERSION = '7.3';
    public const DEFAULT_VERBOSE = true;

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
     * @var InspectionsXml
     */
    private $inspectionProfile;

    /**
     * @var string
     */
    private $phpVersion = self::DEFAULT_PHP_VERSION;

    /**
     * @param string $projectPath
     * @param string $appRootPath
     * @param OutputInterface $output
     * @throws FilesystemException
     * @throws InspectionsProfileException
     */
    public function __construct(
        string $projectPath,
        string $appRootPath,
        OutputInterface $output
    ) {
        $this->projectDirectory = new Directory($projectPath, $output);

        $this->appDirectory = new Directory($appRootPath, $output);

        $this->inspectionProfile = new InspectionsXml(
            $this->appDirectory->getPath() . self::DEFAULT_INSPECTION_PROFILE_PATH
        );
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

    public function getInspectionProfile(): InspectionsXml
    {
        return $this->inspectionProfile;
    }

    public function getPhpVersion(): string
    {
        return $this->phpVersion;
    }

    public function setDockerRepository(string $dockerRepository): void
    {
        $this->dockerRepository = $dockerRepository;
    }

    public function setDockerTag(string $dockerTag): void
    {
        $this->dockerTag = $dockerTag;
    }

    /**
     * @throws ConfigurationException
     * @throws InspectionsProfileException
     */
    public function setInspectionProfile(string $inspectionProfile): void
    {
        if (file_exists($this->projectDirectory->getPath() . '/' . $inspectionProfile)) {
            $this->inspectionProfile = new InspectionsXml(
                $this->projectDirectory->getPath() . '/' . $inspectionProfile
            );
            return;
        }

        if (file_exists($inspectionProfile)) {
            $this->inspectionProfile = new InspectionsXml($inspectionProfile);
            return;
        }

        throw new ConfigurationException(
            'Could not read inspection profile as a path relative to the project directory ('
            . $this->projectDirectory->getPath() . '/' . $inspectionProfile . '), or an absolute path ('
            . $inspectionProfile . ')'
        );
    }

    public function setPhpVersion(string $phpVersion): void
    {
        $this->phpVersion = $phpVersion;
    }
}
