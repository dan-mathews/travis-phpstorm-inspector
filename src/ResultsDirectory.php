<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableDirectory;

class ResultsDirectory extends AbstractCreatableDirectory
{
    private const NAME = 'InspectionResults';

    public function getName(): string
    {
        return self::NAME;
    }
}
