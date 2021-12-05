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
final class DockerRepositoryTest extends AbstractConfigurationTest
{
    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
    public function testSetDockerRepositoryTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'docker-repository' => 3,
                'verbose' => false
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('docker-repository must be a string.');

        $configurationBuilder->build();
    }
}
