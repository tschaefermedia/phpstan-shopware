<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

/**
 * @implements Rule<Variable>
 */
final class NoSuperglobalsRule implements Rule
{
    private const FORBIDDEN_SUPERGLOBALS = [
        '_GET',
        '_POST',
        '_FILES',
        '_REQUEST',
    ];

    public function getNodeType(): string
    {
        return Variable::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!is_string($node->name)) {
            return [];
        }

        if (!in_array($node->name, self::FORBIDDEN_SUPERGLOBALS, true)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(
                sprintf(
                    'Usage of superglobal $%s is forbidden. Use a proper request object instead.',
                    $node->name,
                ),
            )
            ->identifier('shopware.noSuperGlobals')
            ->build(),
        ];
    }
}
