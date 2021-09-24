<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Exceptions;

class DockerException extends \Exception
{
    public function __construct(
        string $message = 'There was an unspecified Docker problem.',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
