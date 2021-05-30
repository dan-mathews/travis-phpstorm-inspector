<?php

declare(strict_types=1);

include dirname(__FILE__) . '/vendor/autoload.php';

use TravisPhpstormInspector\App;

if (!isset($argv[1])) {
    echo 'First argument passed to this script must be a path to the project root';

    exit(1);
}

$app = new App($argv[1]);

$app->run();
