<?php

declare(strict_types=1);

namespace Tests\src;

use PHPUnit\Framework\TestCase;
use Tests\TestConstants;
use Tests\TestHelpers;
use TravisPhpstormInspector\App;

final class AppTest extends TestCase
{
    public function testRun()
    {
        try {
            $app = new App(TestConstants::PROJECT_PATH, TestConstants::INSPECTIONS_PATH);

            $app->run();
        } catch (\Throwable $e) {
            self::fail($e->getMessage());
        }
    }

    public function tearDown(): void
    {
        TestHelpers::removeIdeaDirectory();

        parent::tearDown();
    }
}