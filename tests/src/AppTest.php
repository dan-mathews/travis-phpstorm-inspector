<?php

declare(strict_types=1);

namespace Tests\src;

use PHPUnit\Framework\TestCase;
use Tests\TestConstants;
use Tests\TestHelpers;
use TravisPhpstormInspector\App;

final class AppTest extends TestCase
{
    private const NON_DIRECTORY_PROJECT_ROOT = 'nonDirectoryProjectRoot.txt';

    private const NON_EXISTENT = 'nonExistentProjectElement';

    protected function setUp(): void
    {
        //TODO handle this kind of setUp with a TestProject class which  makes whole testProject from scratch using
        // constants it contains, which can then be accessed later by tests, making clear that they are valid paths etc.
        // it could also contain a tearDown method to clean up after the run
        if (!file_exists(TestConstants::INSPECTIONS_PATH)) {
            throw new \LogicException(TestConstants::INSPECTIONS_PATH . " must exist for tests to run logically.");
        }

        parent::setUp();
    }

    public function testExceptionGivenNonExistentProjectRoot(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new App(self::NON_EXISTENT, TestConstants::INSPECTIONS_PATH);
    }

    public function testExceptionGivenNonDirectoryProjectRoot(): void
    {
        $file = fopen(self::NON_DIRECTORY_PROJECT_ROOT, 'wb');

        if (!$file) {
            throw new \RuntimeException('Could not create ' . self::NON_DIRECTORY_PROJECT_ROOT);
        }

        if (!fclose($file)) {
            throw new \RuntimeException('Could not close ' . self::NON_DIRECTORY_PROJECT_ROOT);
        }

        $this->expectException(\InvalidArgumentException::class);

        new App(self::NON_DIRECTORY_PROJECT_ROOT, TestConstants::INSPECTIONS_PATH);
    }

    //TODO check exception if project root is not writable?

    //These should probably be in InspectionsXmlTest
    //TODO testExceptionGivenNonExistentInspectionsFile
    //TODO testExceptionGivenInvalidInspectionsFile

    //TODO testRun using self::getActualOutput()?

    public function tearDown(): void
    {
        if (file_exists(self::NON_DIRECTORY_PROJECT_ROOT)) {
            unlink(self::NON_DIRECTORY_PROJECT_ROOT);
        }

        TestHelpers::removeIdeaDirectory();

        parent::tearDown();
    }
}