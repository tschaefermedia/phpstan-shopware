<?php

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<Class_>
 *
 * @internal
 */
class MethodBecomesAbstractRule implements Rule
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider)
    {
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

        $extendedClass = $this->reflectionProvider->getClass($node->extends->toString());

        $errors = [];

        $existingMethods = [];

        foreach ($node->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $existingMethods[] = (string)$stmt->name;
            }
        }

        /** @var ClassReflection $parent */
        foreach ([$extendedClass, ...$extendedClass->getParents()] as $parent) {
            foreach ($parent->getNativeReflection()->getMethods() as $method) {
                if (
                    !$method->isAbstract() &&
                    ($method->isPublic() || $method->isProtected()) &&
                    str_contains($method->getDocComment(), '@abstract') &&
                    !in_array($method->getName(), $existingMethods, true)
                ) {
                    $errors[] = RuleErrorBuilder::message(sprintf('Method %s::%s becomes abstract, but is not declared in the extending class. Implement the method for compatibility with next major version.', $parent->getName(), $method->getName()))
                        ->line($node->getLine())
                        ->identifier('shopware.method.becomes.abstract')
                        ->build();
                }
            }
        }


        return $errors;
    }
}