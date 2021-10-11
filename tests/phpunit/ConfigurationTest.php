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

        /**
         * phpstan and psalm doesn't understand the reveal() method
         * @psalm-suppress PropertyTypeCoercion
         * @phpstan-ignore-next-line
         */
        $this->outputDummy = $this->outputProphesy->reveal();

        parent::setUp();
    }

    public function testReadFromConfigFileOnly(): void
    {
        $profilePath = realpath(self::TEST_INSPECTION_PROFILE_PATH);

        $this->writeConfigurationFile(
            [
                'docker-tag' => 'docker-tag-from-config',
                'docker-repository' => 'docker-repository-from-config',
                'ignore-severities' => [
                    'ERROR',
                    'SERVER PROBLEM',
                    'INFORMATION'
                ],
                'profile' => $profilePath,
                'php-version' => '7.4',
                'ignore-lines' => [
                    'file.php' => [1, 5]
                ],
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

        self::assertSame($this->projectPath, $configuration->getProjectDirectory()->getPath());
        self::assertSame(realpath(self::APP_ROOT_PATH), $configuration->getAppDirectory()->getPath());
        self::assertFalse($configuration->getVerbose());
        self::assertSame('docker-tag-from-config', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-config', $configuration->getDockerRepository());
        self::assertSame(
            [
                'ERROR',
                'SERVER PROBLEM',
                'INFORMATION'
            ],
            $configuration->getIgnoreSeverities()
        );
        self::assertSame($profilePath, $configuration->getInspectionProfilePath());
        self::assertSame('7.4', $configuration->getPhpVersion());
        self::assertSame(
            [
            'file.php' => [1, 5]
            ],
            $configuration->getIgnoreLines()
        );
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
                'ignore-lines' => [
                    'file.php' => ['*']
                ],
            ]
        );

        $options = [
            'docker-tag' => 'docker-tag-from-arg',
            'docker-repository' => 'docker-repository-from-arg',
            'ignore-severities' => 'TYPO,WEAK WARNING,WARNING',
            'profile' => self::DEFAULT_INSPECTION_PROFILE_PATH,
            'verbose' => true,
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

        self::assertSame($this->projectPath, $configuration->getProjectDirectory()->getPath());
        self::assertSame(realpath(self::APP_ROOT_PATH), $configuration->getAppDirectory()->getPath());
        self::assertTrue($configuration->getVerbose());
        self::assertSame('docker-tag-from-arg', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-arg', $configuration->getDockerRepository());
        self::assertSame(['TYPO', 'WEAK WARNING', 'WARNING'], $configuration->getIgnoreSeverities());
        self::assertSame(self::DEFAULT_INSPECTION_PROFILE_PATH, $configuration->getInspectionProfilePath());
        self::assertSame('8.0', $configuration->getPhpVersion());
        self::assertSame(
            [
                'file.php' => ['*']
            ],
            $configuration->getIgnoreLines()
        );
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

        self::assertSame($this->projectPath, $configuration->getProjectDirectory()->getPath());
        self::assertSame(realpath(self::APP_ROOT_PATH), $configuration->getAppDirectory()->getPath());
        self::assertFalse($configuration->getVerbose());
        self::assertSame('docker-tag-from-arg', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-arg', $configuration->getDockerRepository());
        self::assertSame(['TYPO', 'WEAK WARNING', 'WARNING'], $configuration->getIgnoreSeverities());
        self::assertSame(self::TEST_INSPECTION_PROFILE_PATH, $configuration->getInspectionProfilePath());
        self::assertSame('7.4', $configuration->getPhpVersion());
        // This cannot be set via command line, so we check it's default.
        self::assertSame([], $configuration->getIgnoreLines());
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
        self::assertSame([], $configuration->getIgnoreSeverities());
        self::assertSame(realpath(self::DEFAULT_INSPECTION_PROFILE_PATH), $configuration->getInspectionProfilePath());
        self::assertSame('7.3', $configuration->getPhpVersion());
        self::assertSame([], $configuration->getIgnoreLines());
    }

    public function testSetInspectionProfileRelativePath(): void
    {
        $this->writeFile($this->projectPath . '/profile.xml', '');

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'profile' => 'profile.xml',
                'verbose' => false
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame(
            $this->projectPath . '/profile.xml',
            $configuration->getInspectionProfilePath()
        );
    }

    public function testSetDockerRepositoryTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'docker-repository' => 3,
                'verbose' => false
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('docker-repository must be a string.');

        $configurationBuilder->build();
    }

    public function testSetDockerTagTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'docker-tag' => 3,
                'verbose' => false
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('docker-tag must be a string.');

        $configurationBuilder->build();
    }

    public function testSetInspectionProfileTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'profile' => 3,
                'verbose' => false
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('profile must be a string.');

        $configurationBuilder->build();
    }

    public function testSetInspectionProfileNonExistent(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'profile' => 'profile.xml',
                'verbose' => false
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Could not read inspection profile as a path relative to the project directory '
            . '(' . $this->projectPath . '/profile.xml), '
            . 'or an absolute path (profile.xml)'
        );

        $configurationBuilder->build();
    }

    public function testSetPhpVersionTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'php-version' => 3,
                'verbose' => false
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('php-version must be a string.');

        $configurationBuilder->build();
    }

    /**
     * @dataProvider invalidIgnoreLinesProvider
     * @param mixed $invalidValue
     * @param string $expectedErrorMessage
     */
    public function testSetIgnoreLinesTypeError($invalidValue, string $expectedErrorMessage): void
    {
        $this->writeConfigurationFile(
            [
                'ignore-lines' => $invalidValue
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
        $this->expectExceptionMessage($expectedErrorMessage);

        $configurationBuilder->build();
    }

    /**
     * @return \Generator<string, array>
     */
    public function invalidIgnoreLinesProvider(): \Generator
    {
        $basicTypeMessage = 'ignore-lines must be an array.';

        yield 'invalid integer value' => [
            1,
            $basicTypeMessage
        ];

        yield 'invalid string value' => [
            'cat',
            $basicTypeMessage
        ];

        $fullFormatMessage = 'Ignore lines must be an object in the format {"index.php": [23, 36], "User.php": ["*"]}.';

        yield 'valid filename key with invalid integer value' => [
            ['valid.string' => 1],
            $fullFormatMessage
        ];

        yield 'valid filename key with invalid string value' => [
            ['valid.string' => 'cat'],
            $fullFormatMessage
        ];

        yield 'valid filename key with invalid array of strings' => [
            ['valid.string' => ['*', '*']],
            $fullFormatMessage
        ];

        yield 'valid filename key with invalid array of arrays' => [
            ['valid.string' => [[], []]],
            $fullFormatMessage
        ];

        yield 'invalid integer key with valid line number array' => [
            [1 => [1, 2]],
            $fullFormatMessage
        ];
    }

    public function testSetIgnoreSeveritiesOptionsTypeError(): void
    {
            $configurationBuilder = new ConfigurationBuilder(
                ['project-path' => $this->projectPath],
                [
                    'verbose' => false,
                    'ignore-severities' => 3,
                ],
                self::APP_ROOT_PATH,
                $this->projectPath,
                $this->outputDummy
            );

            $this->expectException(ConfigurationException::class);
            $this->expectExceptionMessage('The ignore-severities command line option must be a string.');

            $configurationBuilder->build();
    }

    public function testSetIgnoreSeveritiesInvalidValueError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'verbose' => false,
                'ignore-severities' => 'cat',
            ],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid values for ignore severities. The allowed values are: TYPO, WEAK WARNING, WARNING, ERROR, '
            . 'SERVER PROBLEM, INFORMATION.'
        );

        $configurationBuilder->build();
    }

    public function testProjectPathTypeError(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('project-path must be a string.');

        new ConfigurationBuilder(
            ['project-path' => 0],
            [],
            self::APP_ROOT_PATH,
            $this->projectPath,
            $this->outputDummy
        );
    }

    public function testSetIgnoredSeveritiesConfigFileTypeError(): void
    {
        $this->writeConfigurationFile(
            [
                'ignore-severities' => 3
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
        $this->expectExceptionMessage('ignore-severities in the configuration file must be an array.');

        $configurationBuilder->build();
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
