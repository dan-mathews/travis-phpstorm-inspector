<?php

declare(strict_types=1);

namespace PhpUnitTests\ConfigurationTests;

use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\Exceptions\InspectionsProfileException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;

/**
 * @covers \TravisPhpstormInspector\Builders\ConfigurationBuilder
 * @covers \TravisPhpstormInspector\Configuration
 */
final class OrderOfPrecedenceTest extends AbstractConfigurationTest
{
    /**
     * @throws InvalidArgumentException
     * @throws ConfigurationException
     * @throws ExpectationFailedException
     * @throws \JsonException
     * @throws FilesystemException
     * @throws InspectionsProfileException
     */
    public function testReadFromConfigFileOnly(): void
    {
        $profilePath = self::TEST_INSPECTION_PROFILE_PATH;

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
        self::assertEquals(
            new InspectionProfileXml($profilePath),
            $configuration->getInspectionProfile()
        );
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

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ConfigurationException
     * @throws \JsonException
     * @throws FilesystemException
     */
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
        self::assertEquals(
            new InspectionProfileXml(self::DEFAULT_INSPECTION_PROFILE_PATH),
            $configuration->getInspectionProfile()
        );
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

    /**
     * @throws InvalidArgumentException
     * @throws ConfigurationException
     * @throws ExpectationFailedException
     * @throws FilesystemException
     */
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
        self::assertEquals(
            new InspectionProfileXml(self::TEST_INSPECTION_PROFILE_PATH),
            $configuration->getInspectionProfile()
        );
        self::assertSame('7.4', $configuration->getPhpVersion());
        // This cannot be set via command line, so we check it's default.
        self::assertSame([], $configuration->getIgnoreLines());
        self::assertSame(true, $configuration->getWholeProject());
        self::assertSame([], $configuration->getExcludeFolders());
    }

    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ConfigurationException
     * @throws FilesystemException
     */
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
        self::assertEquals(
            new InspectionProfileXml(self::DEFAULT_INSPECTION_PROFILE_PATH),
            $configuration->getInspectionProfile()
        );
        self::assertSame('7.3', $configuration->getPhpVersion());
        self::assertSame([], $configuration->getIgnoreLines());
        self::assertSame(false, $configuration->getWholeProject());
        self::assertSame([], $configuration->getExcludeFolders());
    }
}
