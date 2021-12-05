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
final class PhpVersionTest extends AbstractConfigurationTest
{
    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
    public function testSetPhpVersionTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'php-version' => 3,
                'verbose' => false
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('php-version must be a string.');

        $configurationBuilder->build();
    }
}
