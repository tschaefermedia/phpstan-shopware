<?php

namespace Shopware\PhpStan\Collector;

use PhpParser\Node;
use PhpParser\Node\PropertyItem;
use PhpParser\Node\Stmt\Property;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\InClassNode;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

/**
 * @phpstan-type Property array{line: int, readonly: bool, visibility: 'public' | 'protected' | 'private', static: bool}
 * 
 * @implements Collector<InClassNode,  array<string, Property>>
 */
class DALEntityCollector implements Collector
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    
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
                    if ($prop instanceof PropertyItem) {
                        $propertyLineNumbers[(string) $prop->name->toString()] =  $property->getLine();
                    }
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