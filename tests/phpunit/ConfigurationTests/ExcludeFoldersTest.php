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
final class ExcludeFoldersTest extends AbstractConfigurationTest
{
    /**
     * @dataProvider invalidExcludeFoldersProvider
     * @param mixed $invalidValue
     * @param string $expectedErrorMessage
     * @throws \JsonException
     * @throws FilesystemException
     * @throws ConfigurationException
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

    /**
     * @throws FilesystemException
     * @throws \JsonException
     * @throws ConfigurationException
     */
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
}
