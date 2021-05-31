<?php

declare(strict_types=1);

namespace Tests\src\IdeaDirectory;

use PHPUnit\Framework\TestCase;
use Tests\TestConstants;
use Tests\TestHelpers;
use TravisPhpstormInspector\IdeaDirectory\SimpleIdeaFactory;

final class SimpleIdeaFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        if (!dir(TestConstants::PROJECT_PATH) instanceof \Directory) {
            self::fail(TestConstants::PROJECT_PATH . " can't be opened as a directory.");
        }

        if (is_dir(TestConstants::IDEA_PATH)) {
            self::fail(TestConstants::IDEA_PATH . " must be removed before test run.");
        }

        parent::setUp();
    }

    public function testCreate(): void
    {
        $simpleIdeaFactory = new SimpleIdeaFactory();

        $simpleIdeaFactory->create(TestConstants::PROJECT_NAME, TestConstants::INSPECTIONS_NAME);

        self::assertDirectoryExists(TestConstants::IDEA_PATH);

        self::assertEquals(
            [
                0 => '.',
                1 => '..',
                2 => 'inspectionProfiles',
                3 => 'modules.xml',
                4 => 'php.xml',
                5 => 'project.iml',
            ],
            scandir(TestConstants::IDEA_PATH)
        );

        self::assertEquals(
            [
                0 => '.',
                1 => '..',
                2 => TestConstants::INSPECTIONS_NAME,
                3 => 'profiles_settings.xml',
            ],
            scandir(TestConstants::IDEA_PATH . '/inspectionProfiles')
        );
    }

    public function tearDown(): void
    {
        TestHelpers::removeIdeaDirectory();

        parent::tearDown();
    }
}