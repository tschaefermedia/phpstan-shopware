<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<New_>
 */
class NoDALFilterByID implements Rule
{
    public function getNodeType(): string
    {
        return New_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->class instanceof Node\Name) {
            return [];
        }

        $className = $node->class->toString();

        if (!in_array($className, [EqualsFilter::class, EqualsAnyFilter::class], true)) {
            return [];
        }

        if (empty($node->args)) {
            return [];
        }

        if (!$node->args[0] instanceof Node\Arg) {
            return [];
        }

        $firstArg = $node->args[0]->value;

        if (!$firstArg instanceof Node\Scalar\String_) {
            return [];
        }

        if (strtolower($firstArg->value) === 'id') {
            return [
                RuleErrorBuilder::message('Using "id" directly in EqualsFilter or EqualsAnyFilter is forbidden. Pass the ids directly to the constructor of Criteria or use setIds instead')
                    ->line($node->getLine())
                    ->identifier('shopware.dal.filterById')
                    ->build(),
            ];
        }

        return [];
    }
}
