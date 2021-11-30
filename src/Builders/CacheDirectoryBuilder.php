<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Directory;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;

/**
 * @implements BuilderInterface<Directory>
 */
class CacheDirectoryBuilder implements BuilderInterface
{
    /**
     * @var InspectionProfileXml
     */
    private $inspectionsXml;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Directory
     */
    private $cacheDirectory;

    /**
     * @throws FilesystemException
     * @throws \RuntimeException
     */
    public function __construct(
        InspectionProfileXml $inspectionsXml,
        OutputInterface $output
    ) {
        $this->inspectionsXml = $inspectionsXml;
        $this->output = $output;

        $userId = posix_geteuid();
        $userInfo = posix_getpwuid($userId);

        if (false === $userInfo) {
            throw new \RuntimeException('Could not retrieve user information, needed to create cache directory');
        }

        $user = $userInfo['name'];

        $cachePath = "/home/$user/.cache/travis-phpstorm-inspector";

        $this->cacheDirectory = new Directory($cachePath, $this->output, true);
    }

    public function build(): void
    {
        //todo
    }

    /**
     * @inheritDoc
     */
    public function getResult()
    {
        return $this->cacheDirectory;
    }
}
