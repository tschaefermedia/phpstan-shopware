<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * @implements Rule<MethodCall>
 */
class NoSymfonySessionInConstructorRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($scope->getFunctionName() !== '__construct') {
            return [];
        }

        $objectType = $scope->getType($node->var);

        $sessionInterfaceType = new ObjectType(
            'Symfony\\Component\\HttpFoundation\\Session\\SessionInterface',
        );

        if ($objectType->isSuperTypeOf($sessionInterfaceType)->maybe()) {
            return [
                RuleErrorBuilder::message(
                    'Symfony Session should not be called in constructor. Consider using it in the method where it\'s needed.',
                )
                    ->identifier('shopware.sessionUsageInConstructor')
                    ->build(),
            ];
        }

        return [];
    }
}
