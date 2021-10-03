<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Exceptions;

abstract class AbstractAppException extends \Exception
{
    abstract public function getHeadlineMessage(): string;
}
