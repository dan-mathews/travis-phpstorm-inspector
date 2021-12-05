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
final class InspectionProfileTest extends AbstractConfigurationTest
{
    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
    public function testSetInspectionProfileTypeError(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'profile' => 3,
                'verbose' => false
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('profile must be a string.');

        $configurationBuilder->build();
    }

    /**
     * @throws FilesystemException
     * @throws ConfigurationException
     */
    public function testSetInspectionProfileNonExistent(): void
    {
        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'profile' => 'profile.xml',
                'verbose' => false
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $this->expectException(InspectionsProfileException::class);
        $this->expectExceptionMessage('The inspections profile at profile.xml does not exist.');

        $configurationBuilder->build();
    }

    /**
     * @throws InspectionsProfileException
     * @throws FilesystemException
     * @throws ExpectationFailedException
     * @throws ConfigurationException
     * @throws InvalidArgumentException
     */
    public function testSetInspectionProfileRelativePath(): void
    {
        $this->writeFile(
            $this->projectPath . '/profile.xml',
            '<profile version="1.0"><option name="myName" value="exampleStandards" /></profile>'
        );

        $configurationBuilder = new ConfigurationBuilder(
            ['project-path' => $this->projectPath],
            [
                'profile' => 'profile.xml',
                'verbose' => false
            ],
            $this->projectPath,
            $this->filesystem,
            $this->outputDummy
        );

        $configurationBuilder->build();
        $configuration = $configurationBuilder->getResult();

        self::assertEquals(
            new InspectionProfileXml($this->projectPath . '/profile.xml'),
            $configuration->getInspectionProfile()
        );
    }
}
