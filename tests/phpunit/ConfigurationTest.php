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
                'exclude-folders' => [
                    'src'
                ],
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame($this->projectPath, $configuration->getProjectDirectory()->getPath());
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
        self::assertSame(
            [
                'src'
            ],
            $configuration->getExcludeFolders()
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
                'exclude-folders' => [
                    'src'
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
            'whole-project' => true,
        ];

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            $options,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame($this->projectPath, $configuration->getProjectDirectory()->getPath());
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
        self::assertSame(true, $configuration->getWholeProject());
        self::assertSame(
            [
                'src'
            ],
            $configuration->getExcludeFolders()
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
            'whole-project' => true,
        ];

        $this->outputProphesy->writeln(
            'Could not find a configuration file at ' . $this->projectPath . '/travis-phpstorm-inspector.json, '
            . 'assuming that command line arguments or defaults are being used'
        )->willReturn(null);

        $configurationBuilder = new ConfigurationBuilder(
            [$this->projectName],
            $options,
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame($this->projectPath, $configuration->getProjectDirectory()->getPath());
        self::assertFalse($configuration->getVerbose());
        self::assertSame('docker-tag-from-arg', $configuration->getDockerTag());
        self::assertSame('docker-repository-from-arg', $configuration->getDockerRepository());
        self::assertSame(['TYPO', 'WEAK WARNING', 'WARNING'], $configuration->getIgnoreSeverities());
        self::assertSame(self::TEST_INSPECTION_PROFILE_PATH, $configuration->getInspectionProfilePath());
        self::assertSame('7.4', $configuration->getPhpVersion());
        // This cannot be set via command line, so we check it's default.
        self::assertSame([], $configuration->getIgnoreLines());
        self::assertSame(true, $configuration->getWholeProject());
        self::assertSame([], $configuration->getExcludeFolders());
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
        self::assertSame(false, $configuration->getWholeProject());
        self::assertSame([], $configuration->getExcludeFolders());
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

    public function testSetConfigurationFileAbsolutePath(): void
    {
        $this->writeFile(
            $this->projectPath . '/myConfig.json',
            '{
                "php-version": "20"
            }'
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'configuration' => $this->projectPath . '/myConfig.json',
                'verbose' => false
            ],
            $this->projectPath,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame(
            '20',
            $configuration->getPhpVersion()
        );
    }

    public function testSetConfigurationFileAbsolutePathValueError(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Could not read the configuration file at ' . $this->projectPath . '/nonExistent.json'
        );

        new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'configuration' => $this->projectPath . '/nonExistent.json',
                'verbose' => false
            ],
            $this->projectPath,
            $this->outputDummy
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

    /**
     * @dataProvider invalidExcludeFoldersProvider
     * @param mixed $invalidValue
     * @param string $expectedErrorMessage
     */
    public function testSetExcludeFoldersTypeError($invalidValue, string $expectedErrorMessage): void
    {
        $this->writeConfigurationFile(
            [
                'exclude-folders' => $invalidValue
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
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
    public function invalidExcludeFoldersProvider(): \Generator
    {
        yield 'invalid integer value' => [
            1,
            'exclude-folders must be an array.'
        ];

        yield 'invalid string value' => [
            'cat',
            'exclude-folders must be an array.'
        ];

        yield 'invalid array of integers' => [
            [1, 2],
            'exclude-folders must be an array of strings.'
        ];

        yield 'invalid array of arrays' => [
            [[], []],
            'exclude-folders must be an array of strings.'
        ];
    }

    public function testSetExcludeFoldersInvalidValueError(): void
    {
        $this->writeConfigurationFile(
            [
                'exclude-folders' => ['invalid']
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
            $this->projectPath,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Folders to exclude must be specified as relative paths from the project root. Could not find: invalid'
        );

        $configurationBuilder->build();
    }

    public function testSetIgnoreSeveritiesOptionsTypeError(): void
    {
            $configurationBuilder = new ConfigurationBuilder(
                ['project-path' => $this->projectPath],
                [
                    'verbose' => false,
                    'ignore-severities' => 3,
                ],
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
            $this->projectPath,
            $this->outputDummy
        );
    }

    public function testSetIgnoreSeveritiesConfigFileTypeError(): void
    {
        $this->writeConfigurationFile(
            [
                'ignore-severities' => 3
            ]
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            ['verbose' => false],
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

    /**
     * @param string $name
     * @return string The full path to the created Directory.
     * @throws \RuntimeException
     */
    private function makeDir(string $name): string
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
