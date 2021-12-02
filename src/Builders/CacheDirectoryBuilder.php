<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\CommandRunner;
use TravisPhpstormInspector\Configuration;
use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;

/**
 * @implements BuilderInterface<Directory>
 */
class CacheDirectoryBuilder implements BuilderInterface
{
    public const DIRECTORY_JETBRAINS = 'JetBrains';
    public const DIRECTORY_PROJECT_COPY = 'projectCopy';
    public const DIRECTORY_RESULTS = 'results';

    /**
     * @var InspectionProfileXml
     */
    private $inspectionsXml;

    /**
     * @var Directory
     */
    private $cacheDirectory;
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
        InspectionProfileXml $inspectionsXml,
        OutputInterface $output,
        CommandRunner $commandRunner
    ) {
        $this->inspectionsXml = $inspectionsXml;
        $this->configuration = $configuration;
        $this->commandRunner = $commandRunner;

        $userId = posix_geteuid();
        $userInfo = posix_getpwuid($userId);

        if (false === $userInfo) {
            throw new \RuntimeException('Could not retrieve user information, needed to create cache directory');
        }

        $user = $userInfo['name'];

        $cachePath = "/home/$user/.cache/travis-phpstorm-inspector";

        $this->cacheDirectory = new Directory($cachePath, $output, true);
    }

    /**
     * @throws FilesystemException
     */
    public function build(): void
    {
        // Make/Create an empty 'JetBrains' directory to house PhpStorm's cache.

        $this->cacheDirectory->setOrCreateSubDirectory(self::DIRECTORY_JETBRAINS);


        // Make/Create an empty 'projectCopy' directory to hold a copy of the user's project.
        // This allows the user to continue to make changes while the inspections are being run.

        $projectCopyDirectory = $this->cacheDirectory->setOrCreateSubDirectory(self::DIRECTORY_PROJECT_COPY);

        $projectCopyDirectory->empty();

        $this->configuration->getProjectDirectory()->copyTo($projectCopyDirectory, ['.idea'], $this->commandRunner);


        // Remove and freshly recreate the '.idea' directory according to the configuration we've received.

        $this->cacheDirectory->removeSubDirectory(IdeaDirectoryBuilder::DIRECTORY_IDEA);

        $ideaDirectoryBuilder = new IdeaDirectoryBuilder(
            $this->cacheDirectory,
            $this->inspectionsXml,
            $this->configuration->getPhpVersion(),
            $this->configuration->getExcludeFolders()
        );

        $ideaDirectoryBuilder->build();


        // Make/Create an empty 'results' directory to hold the results of the inspection.

        $resultsDirectory = $this->cacheDirectory->setOrCreateSubDirectory(self::DIRECTORY_RESULTS);

        $resultsDirectory->empty();
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->cacheDirectory;
    }
}
