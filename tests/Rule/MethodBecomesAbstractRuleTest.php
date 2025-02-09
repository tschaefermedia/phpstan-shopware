<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\MethodBecomesAbstractRule;

/**
 * @internal
 *
 * @extends  RuleTestCase<MethodBecomesAbstractRule>
 */
class MethodBecomesAbstractRuleTest extends RuleTestCase
{
    public function testAnalyse(): void
    {
        $this->analyse([ __DIR__ . '/../data/MethodBecomesAbstractRule/impl.php'], [
            [
                <<<EOF
Method EntityExtension::new becomes abstract, but is not declared in the extending class. Implement the method for compatibility with next major version.
EOF,
                15,
            ],
        ]);
    }

    public function testAnalyseWithNamespace(): void
    {
        $this->analyse([ __DIR__ . '/../data/MethodBecomesAbstractRule/ns-test.php'], [
            [
                <<<EOF
Method MethodBecomesAbstractRule\AbstractClassLocation\EntityExtension::new becomes abstract, but is not declared in the extending class. Implement the method for compatibility with next major version.
EOF,
                19,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new MethodBecomesAbstractRule(self::createReflectionProvider());
    }
}
