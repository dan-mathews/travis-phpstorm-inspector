<?php

declare(strict_types=1);

namespace PhpUnitTests;

use PHPUnit\Framework\TestCase;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

final class ConfigurationTest extends TestCase
{
    private const APP_ROOT_PATH = __DIR__ . '/../../';
    private const DEFAULT_INSPECTION_PROFILE_PATH = self::APP_ROOT_PATH . 'data/default.xml';
    private const TEST_ROOT_PATH = self::APP_ROOT_PATH . 'tests/';
    private const TEST_INSPECTION_PROFILE_PATH = self::TEST_ROOT_PATH . 'data/exampleStandards.xml';

    /**
     * @var string
     */
    private $projectName;

    /**
     * @var string
     */
    private $projectPath;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        $this->projectName = 'phpUnitTest' . random_int(0, 1000);

        if (
            false === mkdir($this->projectName) ||
            false === $projectPath = realpath($this->projectName)
        ) {
            throw new \RuntimeException('Could not make project directory with name ' . $this->projectName);
        }

        $this->projectPath = $projectPath;

        parent::setUp();
    }

    /**
     * @throws ConfigurationException
     * @throws \JsonException
     */
    public function testReadFromConfigFileOnly(): void
    {
        $this->writeConfigurationFile(
            [
                'docker_tag' => 'docker_tag_from_config',
                'docker_repository' => 'docker_repository_from_config',
                'ignored_severities' => [
                    'ERROR',
                    'SERVER PROBLEM',
                    'INFORMATION'
                ],
                'inspectionProfile' => realpath(self::TEST_INSPECTION_PROFILE_PATH)
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            [],
            self::APP_ROOT_PATH,
            $this->projectPath
        );

        $configuration = $configurationBuilder->build();

        self::assertSame('docker_tag_from_config', $configuration->getDockerTag());
        self::assertSame('docker_repository_from_config', $configuration->getDockerRepository());
        self::assertSame(
            [
                'ERROR',
                'SERVER PROBLEM',
                'INFORMATION'
            ],
            $configuration->getIgnoredSeverities()
        );
        self::assertSame(
            realpath(self::TEST_INSPECTION_PROFILE_PATH),
            $configuration->getInspectionProfile()->getPath()
        );
    }

    /**
     * @throws \JsonException
     * @throws ConfigurationException
     */
    public function testArgumentsOverrideConfigFile(): void
    {
        $this->writeConfigurationFile(
            [
                'docker_tag' => 'docker_tag_from_config',
                'docker_repository' => 'docker_repository_from_config',
                'ignored_severities' => [
                    'ERROR',
                    'SERVER PROBLEM',
                    'INFORMATION'
                ],
                'inspectionProfile' => realpath(self::TEST_INSPECTION_PROFILE_PATH)
            ]
        );

        $argumentProfilePath = $this->projectName . '/argumentProfile.xml';

        $this->writeFile($argumentProfilePath, 'argumentProfile');

        $arguments = [
            0 => '',
            1 => $this->projectName,
            2 => 'docker_tag=docker_tag_from_arg',
            3 => 'docker_repository=docker_repository_from_arg',
            4 => 'ignored_severities=["TYPO", "WEAK WARNING", "WARNING"]',
            5 => 'inspectionProfile=argumentProfile.xml',
        ];

        $configurationBuilder = new ConfigurationBuilder(
            $arguments,
            self::APP_ROOT_PATH,
            $this->projectPath
        );

        $configuration = $configurationBuilder->build();

        self::assertSame('docker_tag_from_arg', $configuration->getDockerTag());
        self::assertSame('docker_repository_from_arg', $configuration->getDockerRepository());
        self::assertSame(["TYPO", "WEAK WARNING", "WARNING"], $configuration->getIgnoredSeverities());
        self::assertSame(realpath($argumentProfilePath), $configuration->getInspectionProfile()->getPath());
    }

    /**
     * @throws \JsonException
     * @throws ConfigurationException
     */
    public function testReadFromArgumentsOnly(): void
    {
        $arguments = [
            0 => '',
            1 => $this->projectName,
            2 => 'docker_tag=docker_tag_from_arg',
            3 => 'docker_repository=docker_repository_from_arg',
            4 => 'ignored_severities=["TYPO", "WEAK WARNING", "WARNING"]',
            5 => 'inspectionProfile=' . realpath(self::TEST_INSPECTION_PROFILE_PATH),
        ];

        $configurationBuilder = new ConfigurationBuilder(
            $arguments,
            self::APP_ROOT_PATH,
            $this->projectPath
        );

        $configuration = $configurationBuilder->build();

        self::assertSame('docker_tag_from_arg', $configuration->getDockerTag());
        self::assertSame('docker_repository_from_arg', $configuration->getDockerRepository());
        self::assertSame(["TYPO", "WEAK WARNING", "WARNING"], $configuration->getIgnoredSeverities());
        self::assertSame(
            realpath(self::TEST_INSPECTION_PROFILE_PATH),
            $configuration->getInspectionProfile()->getPath()
        );
    }

    /**
     * @throws \JsonException
     * @throws ConfigurationException
     */
    public function testDefaults(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            [],
            self::APP_ROOT_PATH,
            $this->projectPath
        );

        $configuration = $configurationBuilder->build();

        self::assertSame('latest', $configuration->getDockerTag());
        self::assertSame('danmathews1/phpstorm', $configuration->getDockerRepository());
        self::assertSame([], $configuration->getIgnoredSeverities());
        self::assertSame(
            realpath(self::DEFAULT_INSPECTION_PROFILE_PATH),
            $configuration->getInspectionProfile()->getPath()
        );
    }

    /**
     * @param mixed $contents
     * @throws \JsonException
     */
    private function writeConfigurationFile($contents): void
    {
        $this->writeFile(
            $this->projectName . '/travis-phpstorm-inspector.json',
            json_encode($contents, JSON_THROW_ON_ERROR)
        );
    }

    private function writeFile(string $name, string $contents): void
    {
        file_put_contents($name, $contents);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(new \DirectoryIterator($this->projectName));
        parent::tearDown();
    }

    private function removeDirectory(\DirectoryIterator $directoryIterator): void
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
}
