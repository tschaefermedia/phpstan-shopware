<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Testing\RuleTestCase;
use PHPStan\Rules\Rule;
use Shopware\PhpStan\Rule\ForbidGlobBraceRule;

/**
 * @extends RuleTestCase<ForbidGlobBraceRule>
 */
final class ForbidGlobBraceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidGlobBraceRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/../data/ForbidGlobBraceRule/glob.php'], [
            [
                'Usage of GLOB_BRACE constant is forbidden. GLOB_BRACE is not supported on any platform.',
                5,
            ],
            [
                'Usage of GLOB_BRACE constant is forbidden. GLOB_BRACE is not supported on any platform.',
                8,
            ],
        ]);
    }
}
