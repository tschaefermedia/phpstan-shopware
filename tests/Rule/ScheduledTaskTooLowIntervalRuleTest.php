<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use Shopware\PhpStan\Rule\ScheduledTaskTooLowIntervalRule;

class ScheduledTaskTooLowIntervalRuleTest extends RuleTestCase
{
    public function testAnalyse(): void
    {
        $this->analyse([__DIR__ . '/fixtures/ScheduledTaskTooLowIntervalRule/too-low-interval.php'], [
            [
                'Scheduled task has an interval of 1 seconds, it should have an minimum of 3600 seconds.',
                14,
            ],
        ]);
    }

    protected function getRule(): Rule
    {
        return new ScheduledTaskTooLowIntervalRule();
    }
}
