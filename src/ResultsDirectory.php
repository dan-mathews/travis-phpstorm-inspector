<?php

declare(strict_types=1);

namespace TravisPhpstormInspector;

use TravisPhpstormInspector\IdeaDirectory\AbstractCreatableDirectory;

class ResultsDirectory extends AbstractCreatableDirectory
{
    private const NAME = 'travis-phpstorm-inspector-results';

    /**
     * @var bool
     */
    protected $overwrite = true;

    public function getName(): string
    {
        return self::NAME;
    }
}
