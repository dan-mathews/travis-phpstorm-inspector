<?php

declare(strict_types=1);

namespace PhpUnitTests\ConfigurationTests;

use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;

/**
 * @covers \TravisPhpstormInspector\Builders\ConfigurationBuilder
 * @covers \TravisPhpstormInspector\Configuration
 */
final class ConfigurationFileTest extends AbstractConfigurationTest
{
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
            $this->outputDummy
        );
    }
}
