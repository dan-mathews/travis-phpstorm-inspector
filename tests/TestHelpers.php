<?php

declare(strict_types=1);

namespace Tests;

class TestHelpers
{
    public static function removeIdeaDirectory(): void
    {
        if (!is_dir(TestConstants::IDEA_PATH)){
            return;
        }

        self::removeDirectory(new \DirectoryIterator(TestConstants::IDEA_PATH));
    }

    private static function removeDirectory(\DirectoryIterator $directoryIterator): void
    {
        foreach ($directoryIterator as $info) {

            if ($info->isDot()) {
                continue;
            }

            if ($info->isDir()) {
                self::removeDirectory(new \DirectoryIterator($info->getRealPath()));
                continue;
            }

            if ($info->isFile()) {
                unlink($info->getRealPath());
            }
        }

        rmdir($directoryIterator->getPath());
    }
}