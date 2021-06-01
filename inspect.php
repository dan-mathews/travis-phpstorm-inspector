<?php

declare(strict_types=1);

include __DIR__ . '/vendor/autoload.php';

use TravisPhpstormInspector\App;

set_error_handler(static function ($err_severity, $err_msg){
    throw new RuntimeException($err_msg);
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

if (!isset($argv[1])) {
    throw new InvalidArgumentException('First argument passed to this script must be a path to the project root.');
}

if (!isset($argv[2])) {
    throw new InvalidArgumentException('Second argument passed to this script must be a path to the inspections xml file.');
}

$app = new App($argv[1], $argv[2]);

$app->run();
