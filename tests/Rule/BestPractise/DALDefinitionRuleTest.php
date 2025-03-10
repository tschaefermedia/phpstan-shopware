<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\BestPractise;

use PHPStan\Rules\Rule;
use Shopware\PhpStan\Collector\DALDefinitionCollector;
use Shopware\PhpStan\Collector\DALEntityCollector;
use Shopware\PhpStan\Rule\BestPractise\DALDefinitionRule;

class DALDefinitionRuleTest extends \PHPStan\Testing\RuleTestCase
{
    protected function getRule(): Rule
    {
        return new DALDefinitionRule();
    }

    protected function getCollectors(): array
    {
        return [
            new DALDefinitionCollector(self::createReflectionProvider()),
            new DALEntityCollector(),
        ];
    }

    public function testMissingProperty(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/missing-property.php'], [
            [
                'The field "name" in the definition "foo" is not defined in the entity "Shopware\Tests\Rule\BestPractise\fixtures\DALDefinitionRule\FooEntity".',
                1,
            ],
        ]);
    }

    public function testMissingGetterSetter(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/missing-getter-setter.php'], [
            [
                'The field "name" in the definition "foo" is protected, but has no getter method',
                39,
            ],
            [
                'The field "name" in the definition "foo" is protected, but has no setter method',
                39,
            ]
        ]);
    }


    public function testPublicProperty(): void
    {
        $this->analyse([__DIR__ . '/fixtures/DALDefinitionRule/public-property.php'], []);
    }
}