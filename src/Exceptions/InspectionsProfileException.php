<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Exceptions;

class InspectionsProfileException extends AbstractAppException
{
    public function getHeadlineMessage(): string
    {
        return 'Failed to complete inspections because of a probable error in the inspections profile.';
    }
}
