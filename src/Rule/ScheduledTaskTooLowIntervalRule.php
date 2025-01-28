<?php

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

/**
 * @implements Rule<ClassMethod>
 *
 * @internal
 */
class ScheduledTaskTooLowIntervalRule implements Rule
{
    private const MIN_SCHEDULED_TASK_INTERVAL = 3600;

    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ((string) $node->name !== 'getDefaultInterval') {
            return [];
        }

        $class = $scope->getClassReflection();


        if ($class === null || !$class->isSubclassOf(ScheduledTask::class)) {
            return [];
        }

        foreach ($node->stmts ?? [] as $stmt) {
            if ($stmt instanceof Return_ && $stmt->expr instanceof LNumber) {
                $interval = (int) $stmt->expr->value;

                if ($interval < self::MIN_SCHEDULED_TASK_INTERVAL) {
                    return [
                        RuleErrorBuilder::message(\sprintf(
                            'Scheduled task has an interval of %d seconds, it should have an minimum of %d seconds.',
                            $interval,
                            self::MIN_SCHEDULED_TASK_INTERVAL,
                        ))
                            ->identifier('shopware.scheduledTaskLowInterval')
                            ->build(),
                    ];
                }
            }
        }

        return [];
    }
}
