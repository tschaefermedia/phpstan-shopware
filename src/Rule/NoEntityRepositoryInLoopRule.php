<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\For_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 * @implements Rule<Node>
 */
class NoEntityRepositoryInLoopRule implements Rule
{
    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof For_ && !$node instanceof Foreach_) {
            return [];
        }

        /** @var list<IdentifierRuleError> $errors */
        $errors = [];
        $this->checkNode($node, $scope, $errors);

        return $errors;
    }

    /**
     * @param list<IdentifierRuleError> $errors
     */
    private function checkNode(Node $node, Scope $scope, array &$errors): void
    {
        if ($node instanceof MethodCall) {
            $callerType = $scope->getType($node->var);

            if ($callerType->isObject()->yes() && in_array(EntityRepository::class, $callerType->getObjectClassNames(), true)) {
                $errors[] = RuleErrorBuilder::message('EntityRepository method calls are not allowed within loops. This can lead to unexpected N:1 queries.')
                    ->identifier('shopware.noEntityRepositoryInLoop')
                    ->build();
            }
        }

        foreach ($node->getSubNodeNames() as $subNodeName) {
            $subNode = $node->$subNodeName;

            if ($subNode instanceof Node) {
                $this->checkNode($subNode, $scope, $errors);
            }

            if (is_array($subNode)) {
                foreach ($subNode as $subSubNode) {
                    if ($subSubNode instanceof Node) {
                        $this->checkNode($subSubNode, $scope, $errors);
                    }
                }
            }
        }
    }
}
