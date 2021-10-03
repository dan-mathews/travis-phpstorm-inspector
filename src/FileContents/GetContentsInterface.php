<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\FileContents;

interface GetContentsInterface
{
    public function getContents(): string;
}
