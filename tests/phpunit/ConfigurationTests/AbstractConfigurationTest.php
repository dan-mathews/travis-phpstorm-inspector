<?php

declare(strict_types=1);

namespace PhpUnitTests\ConfigurationTests;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @covers \TravisPhpstormInspector\Builders\ConfigurationBuilder
 * @covers \TravisPhpstormInspector\Configuration
 */
abstract class AbstractConfigurationTest extends TestCase
{
    protected const APP_ROOT_PATH = __DIR__ . '/../../../../travis-phpstorm-inspector/';
    protected const DEFAULT_INSPECTION_PROFILE_PATH = self::APP_ROOT_PATH . 'data/default.xml';
    protected const TEST_ROOT_PATH = self::APP_ROOT_PATH . 'tests/';
    protected const TEST_INSPECTION_PROFILE_PATH = self::TEST_ROOT_PATH . 'data/exampleStandards.xml';

    /**
     * @var string
     */
    protected $projectName;

    /**
     * @var string
     */
    protected $projectPath;

    /**
     * @var Prophet
     */
    protected $prophet;

    /**
     * @var ObjectProphecy
     */
    protected $outputProphesy;

    /**
     * @var OutputInterface
     */
    protected $outputDummy;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->projectName = 'phpUnitTest' . random_int(0, 1000);

        $projectPath = $this->makeDir($this->projectName);

        $this->projectPath = $projectPath;

        $this->makeDir($projectPath . '/' . 'src');

        $this->prophet = new Prophet();

        $this->outputProphesy = $this->prophet->prophesize(OutputInterface::class);

        /**
         * phpstan and psalm doesn't understand the reveal() method
         * @psalm-suppress PropertyTypeCoercion
         * @phpstan-ignore-next-line
         */
        $this->outputDummy = $this->outputProphesy->reveal();

        $this->filesystem = new Filesystem();

        parent::setUp();
    }

    /**
     * @param mixed $contents
     * @throws \JsonException
     */
    protected function writeConfigurationFile($contents): void
    {
        $this->writeFile(
            $this->projectName . '/travis-phpstorm-inspector.json',
            json_encode($contents, JSON_THROW_ON_ERROR)
        );
    }

    protected function writeFile(string $name, string $contents): void
    {
        file_put_contents($name, $contents);
    }

    protected function tearDown(): void
    {
        $this->prophet->checkPredictions();
        $this->removeDirectory(new \DirectoryIterator($this->projectName));
        parent::tearDown();
    }

    protected function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        foreach ($directoryIterator as $info) {
            if ($info->isDot()) {
                continue;
            }

            $realPath = $info->getRealPath();

            if (false === $realPath) {
                throw new \RuntimeException('Could not get real path of ' . var_export($info, true));
            }

            if ($info->isDir()) {
                self::removeDirectory(new \DirectoryIterator($realPath));
                continue;
            }

            if ($info->isFile()) {
                unlink($realPath);
            }
        }

        rmdir($directoryIterator->getPath());
    }

    /**
     * @param string $name
     * @return string The full path to the created Directory.
     * @throws \RuntimeException
     */
    protected function makeDir(string $name): string
    {
        if (
            false === mkdir($name) ||
            false === $projectPath = realpath($name)
        ) {
            throw new \RuntimeException('Could not make project directory with name ' . $name);
        }

        return $projectPath;
    }
}
