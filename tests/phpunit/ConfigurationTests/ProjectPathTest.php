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
final class ProjectPathTest extends AbstractConfigurationTest
{
    /**
     * @throws FilesystemException
     */
    public function testProjectPathTypeError(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('project-path must be a string.');

        new ConfigurationBuilder(
            ['project-path' => 0],
            [],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );
    }
}
