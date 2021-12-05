<?php

declare(strict_types=1);

namespace PhpUnitTests\ConfigurationTests;

use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;

/**
 * @covers \TravisPhpstormInspector\Builders\ConfigurationBuilder
 * @covers \TravisPhpstormInspector\Configuration
 */
final class IgnoreSeveritiesTest extends AbstractConfigurationTest
{
    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
    public function testSetIgnoreSeveritiesOptionsTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'verbose' => false,
                'ignore-severities' => 3,
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The ignore-severities command line option must be a string.');

        $configurationBuilder->build();
    }

    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
    public function testSetIgnoreSeveritiesInvalidValueError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'verbose' => false,
                'ignore-severities' => 'cat',
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage(
            'Invalid values for ignore severities. The allowed values are: TYPO, WEAK WARNING, WARNING, ERROR, '
            . 'SERVER PROBLEM, INFORMATION.'
        );

        $configurationBuilder->build();
    }

    /**
     * @throws FilesystemException
     * @throws \JsonException
     * @throws ConfigurationException
     */
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
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('ignore-severities in the configuration file must be an array.');

        $configurationBuilder->build();
    }
}
