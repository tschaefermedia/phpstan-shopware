<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\BestPractise;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\BestPractise\PreferRouteEventRule;

class PreferRouteEventListenerRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PreferRouteEventRule();
    }

    public function testPreferRouteEventListenerRule(): void
    {
        $this->analyse([__DIR__ . '/fixtures/PreferRouteEventRule/listener.php'], [
            [
                'Prefer listen on foo.request event instead of kernel.request. This improves the performance of the application as the event listener is only called if the route matches.',
                14,
            ],
            [
                'Prefer listen on foo.request event instead of kernel.request. This improves the performance of the application as the event listener is only called if the route matches.',
                21,
            ],
        ]);
    }

    public function testPreferRouteEventListenerRuleWithSubscriber(): void
    {
        $this->analyse([__DIR__ . '/fixtures/PreferRouteEventRule/subscriber.php'], [
            [
                'Prefer listen on foo.request event instead of kernel.request. This improves the performance of the application as the event listener is only called if the route matches.',
                26,
            ],
            [
                'Prefer listen on foo.response event instead of kernel.response. This improves the performance of the application as the event listener is only called if the route matches.',
                33,
            ],
            [
                'Prefer listen on foo.controller event instead of kernel.controller. This improves the performance of the application as the event listener is only called if the route matches.',
                40,
            ],
        ]);
    }
}
