<?php

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class Test extends ScheduledTask
{
    public static function getTaskName(): string
    {
        return 'test';
    }

    public static function getDefaultInterval(): int
    {
        return 1;
    }
}
