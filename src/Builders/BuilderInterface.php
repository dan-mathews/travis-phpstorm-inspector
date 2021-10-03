<?php

declare(strict_types=1);

namespace TravisPhpstormInspector\Builders;

/**
 * @template T of object
 */
interface BuilderInterface
{
    public function build(): void;

    /**
     * @return T
     */
    public function getResult(): object;
}
