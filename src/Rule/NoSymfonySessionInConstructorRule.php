<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @implements Rule<InClassMethodNode>
 */
class NoSymfonySessionInConstructorRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ((string) $node->getOriginalNode()->name !== '__construct') {
            return [];
        }

        $nodes = (new NodeFinder())->findInstanceOf($node->getOriginalNode(), MethodCall::class);

        foreach ($nodes as $node) {
            $objectType = $scope->getType($node->var);

            $sessionInterfaceType = new ObjectType(SessionInterface::class);

            if ($sessionInterfaceType->isSuperTypeOf($objectType)->yes()) {
                return [
                    RuleErrorBuilder::message(
                        'Symfony Session should not be called in constructor. Consider using it in the method where it\'s needed.',
                    )
                        ->identifier('shopware.sessionUsageInConstructor')
                        ->line($node->getLine())
                        ->build(),
                ];
            }
        }

        return [];
    }
}
