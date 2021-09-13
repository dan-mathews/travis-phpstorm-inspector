<?php

declare(strict_types=1);

namespace PhpUnitTests;

use PHPUnit\Framework\TestCase;
use TravisPhpstormInspector\Builders\ConfigurationBuilder;

final class ConfigurationTest extends TestCase
{
    public function testReadFromConfigFile(): void
    {
        $name = 'phpUnitTest' . random_int(0, 1000);

        mkdir($name);

        file_put_contents(
            $name . '/travis-phpstorm-inspector.json',
            json_encode(
                [
                    "docker_tag" => "docker_tag_from_config",
                    "docker_repository" => "docker_repository_from_config"
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $arguments = [
            0 => '',
            1 => $name,
        ];

        $configurationBuilder = new ConfigurationBuilder(
            $arguments,
            __DIR__ . '/../../',
            __DIR__ . '/../../'
        );

        $configuration = $configurationBuilder->build();

        self::assertSame('docker_tag_from_config', $configuration->getDockerTag());
        self::assertSame('docker_repository_from_config', $configuration->getDockerRepository());
    }

    public function testArgumentsOverrideConfigFile(): void
    {
        $name = 'phpUnitTest' . random_int(0, 1000);

        mkdir($name);

        file_put_contents(
            $name . '/travis-phpstorm-inspector.json',
            json_encode(
                [
                    "docker_tag" => "docker_tag_from_config",
                    "docker_repository" => "docker_repository_from_config",
                    "ignored_severities" => [
                        "ERROR",
                        "SERVER PROBLEM",
                        "INFORMATION"
                    ]
                ],
                JSON_THROW_ON_ERROR
            )
        );

        $arguments = [
            0 => '',
            1 => $name,
            2 => __DIR__ . '/../data/exampleStandards.xml',
            3 => 'docker_tag=docker_tag_from_arg',
            4 => 'docker_repository=docker_repository_from_arg',
            5 => 'ignored_severities=["TYPO", "WEAK WARNING", "WARNING"]'
        ];

        $configurationBuilder = new ConfigurationBuilder(
            $arguments,
            __DIR__ . '/../../',
            __DIR__ . '/../../'
        );

        $configuration = $configurationBuilder->build();

        self::assertSame('docker_tag_from_arg', $configuration->getDockerTag());
        self::assertSame('docker_repository_from_arg', $configuration->getDockerRepository());
        self::assertSame(["TYPO", "WEAK WARNING", "WARNING"], $configuration->getIgnoredSeverities());
    }
}