<?php

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<ConstFetch>
 */
final class ForbidGlobBraceRule implements Rule
{
    public const ERROR_IDENTIFIER = 'shopware.forbidGlobBrace';

    public function getNodeType(): string
    {
        return ConstFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name->toString() === 'GLOB_BRACE') {
            return [
                RuleErrorBuilder::message(
                    'Usage of GLOB_BRACE constant is forbidden. GLOB_BRACE is not supported on any platform.',
                )
                    ->identifier(self::ERROR_IDENTIFIER)
                    ->build(),
            ];
        }

        return [];
    }
}
