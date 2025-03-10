<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Collector;

use PhpParser\Node;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

/**
 * @phpstan-type EntityProperty array{line: int, readonly: bool, visibility: 'public' | 'protected' | 'private', static: bool}
 *
 * @implements Collector<InClassNode, array{name: string, properties: array<string, EntityProperty>, methods: array<string, array{line: int|false}>}>
 */
class DALEntityCollector implements Collector
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @return array{name: string, properties: array<string, array{line: int, readonly: bool, visibility: 'public'|'protected'|'private', static: bool}>, methods: array<string, array{line: int|false}>}|null
     */
    public function processNode(Node $node, Scope $scope): ?array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return null;
        }

        if (! $classReflection->isSubclassOf(Entity::class)) {
            return null;
        }

        $properties = [];
        $methods = [];
        $propertyLineNumbers = [];

        foreach ($node->getOriginalNode()->stmts as $property) {
            if ($property instanceof Property) {
                foreach ($property->props as $prop) {
                    $propertyLineNumbers[(string) $prop->name->toString()] =  $property->getLine();
                }
            }
        }

        foreach ($classReflection->getNativeReflection()->getProperties() as $property) {
            $properties[$property->getName()] = [
                'line' => $propertyLineNumbers[$property->getName()] ?? 0,
                'readonly' => $property->isReadOnly(),
                'visibility' => $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private'),
                'static' => $property->isStatic(),
            ];
        }

        foreach ($classReflection->getNativeReflection()->getMethods() as $method) {
            $methods[$method->getName()] = [
                'line' => $method->getStartLine(),
            ];
        }

        return [
            'name' => $classReflection->getName(),
            'properties' => $properties,
            'methods' => $methods,
        ];
    }
}
