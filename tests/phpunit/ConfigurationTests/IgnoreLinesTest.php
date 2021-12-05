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
final class IgnoreLinesTest extends AbstractConfigurationTest
{
    /**
     * @dataProvider invalidIgnoreLinesProvider
     * @param mixed $invalidValue
     * @param string $expectedErrorMessage
     * @throws \JsonException
     * @throws FilesystemException
     * @throws ConfigurationException
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
            $this->filesystem,
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
}
