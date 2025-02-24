<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\NoSuperglobalsRule;

/**
 * @extends RuleTestCase<NoSuperglobalsRule>
 */
class NoSuperglobalsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoSuperglobalsRule();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/NoSuperglobals.php'], [
            [
                'Usage of superglobal $_GET is forbidden. Use a proper request object instead.',
                11,
            ],
            [
                'Usage of superglobal $_POST is forbidden. Use a proper request object instead.',
                12,
            ],
            [
                'Usage of superglobal $_FILES is forbidden. Use a proper request object instead.',
                13,
            ],
            [
                'Usage of superglobal $_REQUEST is forbidden. Use a proper request object instead.',
                14,
            ],
        ]);
    }
}
