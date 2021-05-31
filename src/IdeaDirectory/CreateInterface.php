<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\IdeaDirectory;

interface CreateInterface
{
    public function create(string $location): void;
}