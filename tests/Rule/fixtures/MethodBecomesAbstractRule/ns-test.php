<?php

declare(strict_types=1);

namespace MethodBecomesAbstractRule\AbstractClassLocation;

class EntityExtension
{
    public function old(): void {}

    /**
     * @abstract
     */
    public function new(): void {}
}

namespace MethodBecomesAbstractRule\UsageLocation;

class Impl extends \MethodBecomesAbstractRule\AbstractClassLocation\EntityExtension
{
    public function old(): void {}
}
