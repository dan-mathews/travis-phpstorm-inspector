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
final class DockerTagTest extends AbstractConfigurationTest
{
    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
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
}
