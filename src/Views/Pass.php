<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Views;

class Pass implements DisplayInterface
{
    public function display(): void
    {
        echo "No problems to report.\n";
    }
}
