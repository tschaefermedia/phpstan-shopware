<?php

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\NoUserEntityGetStoreTokenRule;

class NoUserEntityGetStoreTokenRuleTest extends RuleTestCase
{
    public function testAnalyse(): void
    {
        $this->analyse([__DIR__ . '/../data/NoUserEntityGetStoreTokenRule/context.php'], [
            [
                'Its not allowed to gather the store token',
                6,
            ],
        ]);
    }


    protected function getRule(): Rule
    {
        return new NoUserEntityGetStoreTokenRule();
    }
}
