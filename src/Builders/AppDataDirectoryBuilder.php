<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use TravisPhpstormInspector\CommandRunner;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\Exceptions\FilesystemException;

/**
 * @implements BuilderInterface<Directory>
 */
class AppDataDirectoryBuilder implements BuilderInterface
{
    public const DIRECTORY_CACHE = 'cache';
    public const DIRECTORY_PROJECT_COPY = 'projectCopy';
    public const DIRECTORY_RESULTS = 'results';
    private const DIRECTORY_STORAGE = '.travis-phpstorm-inspector';

    /**
     * @var Directory
     */
    private $appDataDirectory;
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var CommandRunner
     */
    private $commandRunner;

    /**
     * @throws FilesystemException
     * @throws \RuntimeException
     */
    public function __construct(
        Configuration $configuration,
        OutputInterface $output,
        CommandRunner $commandRunner
    ) {
        $this->configuration = $configuration;
        $this->commandRunner = $commandRunner;

        $userId = posix_geteuid();
        $userInfo = posix_getpwuid($userId);

        if (false === $userInfo) {
            throw new \RuntimeException('Could not retrieve user information, needed to create cache directory');
        }

        $user = $userInfo['name'];

        $cachePath = '/home/' . $user . '/' . self::DIRECTORY_STORAGE;

        $this->appDataDirectory = new Directory($cachePath, $output, new Filesystem(), true);
    }

    /**
     * @throws FilesystemException
     */
    public function build(): void
    {
        // Make/Create a 'cache' directory to house PhpStorm's cache.

        $this->appDataDirectory->setOrCreateSubDirectory(self::DIRECTORY_CACHE);


        // Make/Create an empty 'projectCopy' directory to hold a copy of the user's project.
        // This allows the user to continue to make changes while the inspections are being run.

        $projectCopyDirectory = $this->appDataDirectory->setOrCreateSubDirectory(self::DIRECTORY_PROJECT_COPY);

        $projectCopyDirectory->empty();

        $this->configuration->getProjectDirectory()->copyTo($projectCopyDirectory, ['.idea'], $this->commandRunner);


        // Remove and freshly recreate the '.idea' directory according to the configuration we've received.

        $this->appDataDirectory->removeSubDirectory(IdeaDirectoryBuilder::DIRECTORY_IDEA);

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $this->appDataDirectory,
            $this->configuration->getInspectionProfile(),
            $this->configuration->getPhpVersion(),
            $this->configuration->getExcludeFolders()
        );

        $ideaDirectoryBuilder->build();


        // Make/Create an empty 'results' directory to hold the results of the inspection.

        $resultsDirectory = $this->appDataDirectory->setOrCreateSubDirectory(self::DIRECTORY_RESULTS);

        $resultsDirectory->empty();
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->appDataDirectory;
    }
}