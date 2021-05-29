<?php

declare(strict_types=1);

include dirname(__FILE__) . '/vendor/autoload.php';

use TravisPhpstormInspector\ResultsProcessor;

if (!isset($argv[1])) {
    echo 'First argument passed to this script must be a path to the inspection results directory';

    exit(1);
}

$resultsProcessor = new ResultsProcessor();

$resultsProcessor->process($argv[1]);
