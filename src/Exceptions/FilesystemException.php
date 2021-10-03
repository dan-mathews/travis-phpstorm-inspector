<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Exceptions;

class FilesystemException extends AbstractAppException
{
    public function getHeadlineMessage(): string
    {
        return 'Failed to complete inspections because of a filesystem error.';
    }
}
