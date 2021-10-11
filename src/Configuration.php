<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;

class Configuration
{
    public const DEFAULT_DOCKER_REPOSITORY = 'danmathews1/phpstorm';
    public const DEFAULT_DOCKER_TAG = 'latest';
    public const DEFAULT_IGNORE_LINES = [];
    public const DEFAULT_IGNORE_SEVERITIES = [];
    public const DEFAULT_INSPECTION_PROFILE_PATH = '/data/default.xml';
    public const DEFAULT_PHP_VERSION = '7.3';
    public const DEFAULT_VERBOSE = true;

    public const VALID_IGNORE_SEVERITIES = [
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
    private $ignoreSeverities = self::DEFAULT_IGNORE_SEVERITIES;

    /**
     * @var array<string, array<int|string>>
     */
    private $ignoreLines = self::DEFAULT_IGNORE_LINES;

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
     * @var string
     */
    private $inspectionProfilePath;

    /**
     * @var string
     */
    private $phpVersion = self::DEFAULT_PHP_VERSION;


    /**
     * @param string $projectPath
     * @param string $appRootPath
     * @param OutputInterface $output
     * @throws FilesystemException
     */
    public function __construct(
        string $projectPath,
        string $appRootPath,
        OutputInterface $output
    ) {
        $this->projectDirectory = new Directory($projectPath, $output);

        $this->appDirectory = new Directory($appRootPath, $output);

        $this->inspectionProfilePath = $this->appDirectory->getPath() . self::DEFAULT_INSPECTION_PROFILE_PATH;
    }

    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @param array<mixed> $ignoreSeverities
     * @throws ConfigurationException
     * @psalm-suppress MixedPropertyTypeCoercion - we validate $ignoredSeverities is string[], throwing after array_diff
     */
    public function setIgnoreSeverities(array $ignoreSeverities): void
    {
        if ([] !== array_diff($ignoreSeverities, self::VALID_IGNORE_SEVERITIES)) {
            throw new ConfigurationException(
                'Invalid values for ignore severities. The allowed values are: '
                . implode(', ', self::VALID_IGNORE_SEVERITIES) . '.'
            );
        }

        $this->ignoreSeverities = $ignoreSeverities;
    }

    /**
     * @param array<mixed> $ignoreLines
     * @throws ConfigurationException
     * @psalm-suppress MixedPropertyTypeCoercion - we validate $ignoredSeverities is string[], throwing after array_diff
     */
    public function setIgnoreLines(array $ignoreLines): void
    {
        $errorMessage = 'Ignore lines must be an object in the format {"index.php": [23, 36], "User.php": ["*"]}.';

        foreach ($ignoreLines as $filename => $lineArray) {
            if (
                !\is_string($filename) ||
                !\is_array($lineArray)
            ) {
                throw new ConfigurationException($errorMessage);
            }

            if (['*'] === $lineArray) {
                $this->ignoreLines = $ignoreLines;
                return;
            }

            // The key isn't needed, but for completeness we check it's an integer
            /** @var mixed $line */
            foreach ($lineArray as $key => $line) {
                if (
                    !\is_int($key) ||
                    !\is_int($line)
                ) {
                    throw new ConfigurationException($errorMessage);
                }
            }
        }

        $this->ignoreLines = $ignoreLines;
    }

    /**
     * @return string[]
     */
    public function getIgnoreSeverities(): array
    {
        return $this->ignoreSeverities;
    }

    /**
     * @return array<string, array<int|string>>
     */
    public function getIgnoreLines(): array
    {
        return $this->ignoreLines;
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

    public function getInspectionProfilePath(): string
    {
        return $this->inspectionProfilePath;
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
     */
    public function setInspectionProfilePath(string $inspectionProfilePath): void
    {
        if (file_exists($this->projectDirectory->getPath() . '/' . $inspectionProfilePath)) {
            $this->inspectionProfilePath = $this->projectDirectory->getPath() . '/' . $inspectionProfilePath;
            return;
        }

        if (file_exists($inspectionProfilePath)) {
            $this->inspectionProfilePath = $inspectionProfilePath;
            return;
        }

        throw new ConfigurationException(
            'Could not read inspection profile as a path relative to the project directory ('
            . $this->projectDirectory->getPath() . '/' . $inspectionProfilePath . '), or an absolute path ('
            . $inspectionProfilePath . ')'
        );
    }

    public function setPhpVersion(string $phpVersion): void
    {
        $this->phpVersion = $phpVersion;
    }
}
