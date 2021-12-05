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
final class ConfigurationFileTest extends AbstractConfigurationTest
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
            $this->filesystem,
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
     * @throws FilesystemException
     * @throws ConfigurationException
     * @throws ExpectationFailedException
     * @throws InvalidArgumentException
     */
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
            $this->filesystem,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertSame(
            '20',
            $configuration->getPhpVersion()
        );
    }

    /**
     * @throws FilesystemException
     */
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
            $this->filesystem,
            $this->outputDummy
        );
    }
}
