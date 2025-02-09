<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ClassExtendUsesAbstractClassWhenExisting;

class ClassExtendUsesAbstractClassWhenExistingTest extends RuleTestCase
{
    public function testAnalyse(): void
    {
        $this->analyse([__DIR__ . '/../data/ClassExtendUsesAbstractClassWhenExisting/extends.php'], [
            [
                <<<EOF
Class Plugin should extend AbstractCore to not break typehints
EOF,
                18,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ClassExtendUsesAbstractClassWhenExisting(self::createReflectionProvider());
    }
}
