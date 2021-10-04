<?php

declare(strict_types=1);

namespace PhpUnitTests;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Prophecy\Prophet;
use Symfony\Component\Console\Output\OutputInterface;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Exceptions\ConfigurationException;

/**
 * @covers \TravisPhpstormInspector\Builders\ConfigurationBuilder
 * @covers \TravisPhpstormInspector\Configuration
 */
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
     * @var Prophet
     */
    private $prophet;

    /**
     * @var ObjectProphecy
     */
    private $outputProphesy;

    /**
     * @var OutputInterface
     */
    private $outputDummy;

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

        $this->prophet = new Prophet();

        $this->outputProphesy = $this->prophet->prophesize(OutputInterface::class);

        /** @var OutputInterface $this->outputDummy */
        $this->outputDummy = $this->outputProphesy->reveal();

        parent::setUp();
    }

    public function testReadFromConfigFileOnly(): void
    {
        $this->writeConfigurationFile(
            [
                'docker-tag' => 'docker-tag-from-config',
                'docker-repository' => 'docker-repository-from-config',
                'ignore-severities' => [
                    'ERROR',
                    'SERVER PROBLEM',
                    'INFORMATION'
                ],
                'profile' => realpath(self::TEST_INSPECTION_PROFILE_PATH),
                'php-version' => '7.4',
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame('docker-tag-from-config', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-config', $configuration->getDockerRepository());
        self::assertSame(
            [
                'ERROR',
                'SERVER PROBLEM',
                'INFORMATION'
            ],
            $configuration->getIgnoredSeverities()
        );
        self::assertSame(
            'exampleStandards.xml',
            $configuration->getInspectionProfile()->getName()
        );
        self::assertSame('7.4', $configuration->getPhpVersion());
    }

    public function testCommandLineOverridesConfigFile(): void
    {
        $this->writeConfigurationFile(
            [
                'docker-tag' => 'docker-tag-from-config',
                'docker-repository' => 'docker-repository-from-config',
                'ignore-severities' => [
                    'ERROR',
                    'SERVER PROBLEM',
                    'INFORMATION'
                ],
                'profile' => realpath(self::TEST_INSPECTION_PROFILE_PATH),
                'php-version' => '7.4',
            ]
        );

        $options = [
            'docker-tag' => 'docker-tag-from-arg',
            'docker-repository' => 'docker-repository-from-arg',
            'ignore-severities' => 'TYPO,WEAK WARNING,WARNING',
            'profile' => self::DEFAULT_INSPECTION_PROFILE_PATH,
            'verbose' => false,
            'php-version' => '8.0',
        ];

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            $options,
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame('docker-tag-from-arg', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-arg', $configuration->getDockerRepository());
        self::assertSame(['TYPO', 'WEAK WARNING', 'WARNING'], $configuration->getIgnoredSeverities());
        self::assertSame(
            'default.xml',
            $configuration->getInspectionProfile()->getName()
        );
        self::assertSame('8.0', $configuration->getPhpVersion());
    }

    public function testReadFromCommandLineOnly(): void
    {
        $options = [
            'docker-tag' => 'docker-tag-from-arg',
            'docker-repository' => 'docker-repository-from-arg',
            'ignore-severities' => 'TYPO,WEAK WARNING,WARNING',
            'profile' => self::TEST_INSPECTION_PROFILE_PATH,
            'verbose' => false,
            'php-version' => '7.4',
        ];

        $this->outputProphesy->writeln(
            'Could not find a configuration file at ' . $this->projectPath . '/travis-phpstorm-inspector.json, '
            . 'assuming that command line arguments or defaults are being used'
        )->willReturn(null);

        $configurationBuilder = new ConfigurationBuilder(
            [$this->projectName],
            $options,
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame('docker-tag-from-arg', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-arg', $configuration->getDockerRepository());
        self::assertSame(['TYPO', 'WEAK WARNING', 'WARNING'], $configuration->getIgnoredSeverities());
        self::assertSame(
            'exampleStandards.xml',
            $configuration->getInspectionProfile()->getName()
        );
        self::assertSame('7.4', $configuration->getPhpVersion());
    }

    public function testDefaults(): void
    {
        $this->outputProphesy->writeln(
            'Could not find a configuration file at ' . $this->projectPath . '/travis-phpstorm-inspector.json, '
            . 'assuming that command line arguments or defaults are being used'
        )->willReturn(null);

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame('latest', $configuration->getDockerTag());
        self::assertSame('danmathews1/phpstorm', $configuration->getDockerRepository());
        self::assertSame([], $configuration->getIgnoredSeverities());
        self::assertSame(
            'default.xml',
            $configuration->getInspectionProfile()->getName()
        );
        self::assertSame('7.3', $configuration->getPhpVersion());
    }

    public function testSetDockerRepository(): void
    {
        $this->writeConfigurationFile(
            [
                'docker-repository' => 3,
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $configurationBuilder->build();
    }

    public function testSetDockerTag(): void
    {
        $this->writeConfigurationFile(
            [
                'docker-tag' => 3,
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $configurationBuilder->build();
    }

    public function testSetInspectionProfile(): void
    {
        $this->writeConfigurationFile(
            [
                'profile' => 3,
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $configurationBuilder->build();
    }

    public function testSetPhpVersion(): void
    {
        $this->writeConfigurationFile(
            [
                'php-version' => 3,
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $configurationBuilder->build();
    }

    public function testSetIgnoredSeveritiesFromConfigFile(): void
    {
        $this->writeConfigurationFile(
            [
                'ignore-severities' => 3,
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $configurationBuilder->build();
    }

    public function testSetIgnoredSeveritiesFromCommandLine(): void
    {
        $options = [
            'ignore-severities' => 3,
            'verbose' => false,
        ];

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            $options,
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $configurationBuilder->build();
    }

    public function testConstructor(): void
    {
        $this->expectException(ConfigurationException::class);
        new ConfigurationBuilder(
            ['project-path' => 0],
            [],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
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
        $this->prophet->checkPredictions();
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
