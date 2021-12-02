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
    public const DEFAULT_EXCLUDE_FOLDERS = [];
    public const DEFAULT_IGNORE_LINES = [];
    public const DEFAULT_IGNORE_SEVERITIES = [];
    public const DEFAULT_INSPECTION_PROFILE_PATH = __DIR__ . '/../../travis-phpstorm-inspector/data/default.xml';
    public const DEFAULT_PHP_VERSION = '7.3';
    public const DEFAULT_VERBOSE = true;
    public const DEFAULT_WHOLE_PROJECT = false;

    public const VALID_IGNORE_SEVERITIES = [
        'TYPO',
        'WEAK WARNING',
        'WARNING',
        'ERROR',
        'SERVER PROBLEM',
        'INFORMATION',
    ];

    /**
     * @var array<string>
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
     * @var array<string>
     */
    private $excludeFolders = self::DEFAULT_EXCLUDE_FOLDERS;

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
     * @var bool
     */
    private $wholeProject = self::DEFAULT_WHOLE_PROJECT;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param string $projectPath
     * @param OutputInterface $output
     * @throws FilesystemException
     */
    public function __construct(
        string $projectPath,
        OutputInterface $output
    ) {
        $this->projectDirectory = new Directory($projectPath, $output);

        $realPath = realpath(self::DEFAULT_INSPECTION_PROFILE_PATH);

        if (false === $realPath) {
            throw new FilesystemException('Could not establish default inspection profile path');
        }

        $this->inspectionProfilePath = $realPath;

        $this->output = $output;
    }

    public function setVerbose(bool $verbose): void
    {
        $this->verbose = $verbose;
    }

    /**
     * @param array<mixed> $ignoreSeverities
     * @throws ConfigurationException
     * @psalm-suppress MixedPropertyTypeCoercion - we validate it's an array<string>, throwing after array_diff
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
     * @psalm-suppress MixedPropertyTypeCoercion - we validate it's an array<string>, throwing after array_diff
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
     * @param array<string> $excludeFolders
     * @throws ConfigurationException
     */
    public function setExcludeFolders(array $excludeFolders): void
    {
        foreach ($excludeFolders as $folderName) {
            try {
                // Use Directory to validate that this is a relative path from the project to a real folder.
                new Directory($this->getProjectDirectory()->getPath() . '/' . $folderName, $this->output);
            } catch (FilesystemException $e) {
                throw new ConfigurationException(
                    'Folders to exclude must be specified as relative paths from the project root. '
                    . 'Could not find: ' . $folderName,
                    2,
                    $e
                );
            }

            $this->excludeFolders[] = $folderName;
        }
    }

    /**
     * @return array<string>
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

    /**
     * @return array<string>
     */
    public function getExcludeFolders(): array
    {
        return $this->excludeFolders;
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

    public function getWholeProject(): bool
    {
        return $this->wholeProject;
    }

    public function setWholeProject(bool $wholeProject): void
    {
        $this->wholeProject = $wholeProject;
    }
}
