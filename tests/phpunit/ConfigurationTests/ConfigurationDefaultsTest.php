<?php

declare(strict_types=1);

namespace PhpUnitTests\ConfigurationTests;

use PHPUnit\Framework\ExpectationFailedException;
use SebastianBergmann\RecursionContext\InvalidArgumentException;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;
use TravisPhpstormInspector\Exceptions\ConfigurationException;
use TravisPhpstormInspector\Exceptions\FilesystemException;
use TravisPhpstormInspector\FileContents\InspectionProfileXml;

/**
 * @covers \TravisPhpstormInspector\Builders\ConfigurationBuilder
 * @covers \TravisPhpstormInspector\Configuration
 */
final class ConfigurationDefaultsTest extends AbstractConfigurationTest
{
    /**
     * @throws InvalidArgumentException
     * @throws ExpectationFailedException
     * @throws ConfigurationException
     * @throws FilesystemException
     */
    public function testConfigurationDefaults(): void
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
