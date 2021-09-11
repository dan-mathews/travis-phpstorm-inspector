<?php

declare(strict_types=1);

function includeIfExists(string $filePath): bool
{
    if (file_exists($filePath)) {
        include $filePath;
        return true;
    }

    return false;
}

if (
    !includeIfExists(__DIR__ . '/../../autoload.php') &&
    !includeIfExists(__DIR__ . '/vendor/autoload.php')
) {
    throw new RuntimeException('Could not find autoloaded classes as vendor or main project.');
}

use TravisPhpstormInspector\App;

/** @psalm-suppress InvalidArgument - not all arguments are required here */
set_error_handler(static function (int $_errno, string $errstr) {
    throw new RuntimeException($errstr);
});

function exception_handler(\Throwable $exception): void
{
    echo "\nFailed to complete inspections because of an exception.\n\n"
        . "If you think you've discovered a problem with the travis-phpstorm-inspector project,\n"
        . "please provide some context and a full copy of the exceptions reported below to:\n"
        . "  https://github.com/dan-mathews/travis-phpstorm-inspector/issues/new\n\n"
        . $exception . "\n";

    exit(1);
}

set_exception_handler('exception_handler');

$verbose = false;

if (
    isset($argv[3]) &&
    substr($argv[3], 0, 2) === '-v'
) {
    $verbose = true;
}

if (!isset($argv[1])) {
    throw new InvalidArgumentException('First argument passed to this script must be a path to the project root.');
}

if (!isset($argv[2])) {
    throw new InvalidArgumentException(
        'Second argument passed to the script must be a path to the inspections xml file.'
    );
}

$app = new App($argv[1], $argv[2], $verbose);

$app->run();
