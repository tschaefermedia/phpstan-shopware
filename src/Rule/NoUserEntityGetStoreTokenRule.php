<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Shopware\Core\System\User\UserEntity;

/**
 * @implements Rule<MethodCall>
 */
class NoUserEntityGetStoreTokenRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        if ($node->name->toString() !== 'getStoreToken') {
            return [];
        }

        $callerType = $scope->getType($node->var);
        $userEntityType = new ObjectType(UserEntity::class);

        if (!$userEntityType->isSuperTypeOf($callerType)->yes()) {
            return [];
        }

        return [
            RuleErrorBuilder::message('Its not allowed to gather the store token')
                ->identifier('shopware.noUserEntityGetStoreToken')
                ->line($node->getLine())
                ->build(),
        ];
    }
}
