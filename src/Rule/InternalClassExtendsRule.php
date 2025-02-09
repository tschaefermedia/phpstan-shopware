<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 *
 * @internal
 */
class InternalClassExtendsRule implements Rule
{
    private ReflectionProvider $reflectionProvider;

    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }

    public function getNodeType(): string
    {
        return Class_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->extends === null) {
            return [];
        }

        $parentClassName = (string) $node->extends;
        $parentClassReflection = $this->reflectionProvider->getClass($parentClassName);

        if ($parentClassReflection->isInternal()) {
            return [
                RuleErrorBuilder::message(sprintf('Class %s extends internal class %s. Please refrain from extending classes which are annotated with @internal.', (string) $node->name, $parentClassName))
                    ->line($node->getLine())
                    ->identifier('shopware.internal.class.extends')
                    ->build(),
            ];
        }

        return [];
    }
}
