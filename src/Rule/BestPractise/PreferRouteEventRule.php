<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule\BestPractise;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\NotIdentical;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;

/**
 * @implements Rule<InClassNode>
 */
class PreferRouteEventRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        $errors = [];

        foreach ($node->getOriginalNode()->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $events = $this->getEventsByAttributes($node->getOriginalNode(), $stmt, $classReflection, $scope);

                foreach ($events as $event) {
                    if (!in_array($event['eventName'], ['kernel.request', 'kernel.response', 'kernel.controller'], true)) {
                        continue;
                    }

                    if ($event['methodName'] !== (string) $stmt->name) {
                        continue;
                    }

                    $condition = $this->getRouteCondition($stmt, $scope);

                    if ($condition === null) {
                        continue;
                    }

                    $errors[] = RuleErrorBuilder::message(sprintf('Prefer listen on %s.%s event instead of %s. This improves the performance of the application as the event listener is only called if the route matches.', $condition, str_replace('kernel.', '', $event['eventName']), $event['eventName']))
                        ->line($stmt->getLine())
                        ->identifier('shopware.bestPractise.preferRouteEventListener')
                        ->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @return array<array{eventName: string, methodName: string}>
     */
    private function getEventsByAttributes(ClassLike $classNode, ClassMethod $node, ClassReflection $classReflection, Scope $scope): array
    {
        $events = [];

        foreach ($classReflection->getAttributes() as $attribute) {
            if ($attribute->getName() !== AsEventListener::class) {
                continue;
            }

            $arguments = $attribute->getArgumentTypes();

            $eventName = '';
            $methodName = '';

            // @phpstan-ignore-next-line phpstanApi.instanceofType
            if (isset($arguments['event']) && $arguments['event'] instanceof ConstantStringType) {
                $eventName = $arguments['event']->getValue();
            }

            // @phpstan-ignore-next-line phpstanApi.instanceofType
            if (isset($arguments['method']) && $arguments['method'] instanceof ConstantStringType) {
                $methodName = $arguments['method']->getValue();
            } else {
                $methodName = 'on' . ucfirst($eventName);
            }

            $events[] = [
                'eventName' => $eventName,
                'methodName' => $methodName,
            ];
        }

        foreach ($classReflection->getMethod($node->name->toString(), $scope)->getAttributes() as $attribute) {
            if ($attribute->getName() !== AsEventListener::class) {
                continue;
            }

            $arguments = $attribute->getArgumentTypes();

            // @phpstan-ignore-next-line phpstanApi.instanceofType
            if (isset($arguments['event']) && $arguments['event'] instanceof ConstantStringType) {
                $eventName = $arguments['event']->getValue();
            } else {
                $eventName = 'on' . ucfirst($node->name->toString());
            }

            $events[] = [
                'eventName' => $eventName,
                'methodName' => $node->name->toString(),
            ];
        }

        if ($classReflection->hasMethod('getSubscribedEvents')) {
            foreach ($classNode->stmts as $stmt) {
                if ($stmt instanceof ClassMethod && $stmt->name->toString() === 'getSubscribedEvents') {
                    $returns = (new NodeFinder())->findFirstInstanceOf($stmt, Return_::class);

                    if ($returns === null || $returns->expr === null) {
                        continue;
                    }

                    $returnType = $scope->getType($returns->expr);

                    // @phpstan-ignore-next-line phpstanApi.instanceofType
                    if ($returnType instanceof ConstantArrayType) {
                        foreach ($returnType->getKeyTypes() as $key => $keyType) {
                            // @phpstan-ignore-next-line phpstanApi.instanceofType
                            if ($keyType instanceof ConstantStringType) {
                                $pair = $returnType->getValueTypes()[$key];

                                // @phpstan-ignore-next-line phpstanApi.instanceofType
                                if ($pair instanceof ConstantStringType) {
                                    $events[] = [
                                        'eventName' => $keyType->getValue(),
                                        'methodName' => $pair->getValue(),
                                    ];
                                }

                                // @phpstan-ignore-next-line phpstanApi.instanceofType
                                if ($pair instanceof ConstantArrayType) {
                                    foreach ($pair->getValueTypes() as $pairKey => $pairValue) {
                                        // @phpstan-ignore-next-line phpstanApi.instanceofType
                                        if ($pairValue instanceof ConstantStringType) {
                                            $events[] = ['eventName' => $keyType->getValue(), 'methodName' => $pairValue->getValue()];
                                        }

                                        // @phpstan-ignore-next-line phpstanApi.instanceofType
                                        if ($pairValue instanceof ConstantArrayType && count($pairValue->getValueTypes()) > 0) {
                                            $firstValue = $pairValue->getValueTypes()[0];

                                            // @phpstan-ignore-next-line phpstanApi.instanceofType
                                            if ($firstValue instanceof ConstantStringType) {
                                                $events[] = ['eventName' => $keyType->getValue(), 'methodName' => $firstValue->getValue()];
                                            } else {
                                                $events[] = ['eventName' => $keyType->getValue(), 'methodName' => ''];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $events;
    }

    private function getRouteCondition(Node $node, Scope $scope): ?string
    {
        $ifStatements = (new NodeFinder())->findInstanceOf($node, If_::class);

        foreach ($ifStatements as $ifStatement) {
            if ($ifStatement->cond instanceof NotIdentical) {
                $left = $ifStatement->cond->left;
                $right = $ifStatement->cond->right;

                if ($right instanceof String_ && $this->isRouteGetter($left, $scope)) {
                    return $right->value;
                }
            }
        }

        return null;
    }

    private function isRouteGetter(Node $node, Scope $scope): bool
    {
        if (!($node instanceof MethodCall)) {
            return false;
        }

        if (!$node->name instanceof Node\Identifier) {
            return false;
        }

        if ($node->name->toString() !== 'get') {
            return false;
        }

        if (count($node->args) !== 1) {
            return false;
        }

        $arg = $node->args[0];

        if (!($arg instanceof Arg)) {
            return false;
        }

        if (!($arg->value instanceof String_)) {
            return false;
        }

        if ($arg->value->value !== '_route') {
            return false;
        }

        $propertyFetch = $node->var;

        if (!$propertyFetch instanceof PropertyFetch) {
            return false;
        }

        if (!$propertyFetch->name instanceof Node\Identifier) {
            return false;
        }

        if ($propertyFetch->name->toString() !== 'attributes') {
            return false;
        }

        $symfonyRequest = new ObjectType(Request::class);

        return $scope->getType($propertyFetch->var)->isSuperTypeOf($symfonyRequest)->yes();
    }
}
