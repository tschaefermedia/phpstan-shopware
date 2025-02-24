<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\NoEntityRepositoryInLoopRule;

/**
 * @extends RuleTestCase<NoEntityRepositoryInLoopRule>
 */
class NoEntityRepositoryInLoopRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoEntityRepositoryInLoopRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/NoEntityRepositoryInLoop/NoEntityRepositoryInLoop.php'], [
            [
                'EntityRepository method calls are not allowed within loops. This can lead to unexpected N:1 queries.',
                20,
            ],
            [
                'EntityRepository method calls are not allowed within loops. This can lead to unexpected N:1 queries.',
                28,
            ],
        ]);
    }
}
