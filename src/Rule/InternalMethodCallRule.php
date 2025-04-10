<?php

// Code taken from https://github.com/TemirkhanN/phpstan-internal-rule

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Identifier;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Shopware\PhpStan\Helper\NamespaceChecker;

/**
 * @implements Rule<MethodCall>
 *
 * @internal
 */
class InternalMethodCallRule implements Rule
{
    public function __construct(private readonly ReflectionProvider $reflectionProvider) {}

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Identifier) {
            return [];
        }

        if (true === str_starts_with($scope->getNamespace() ?? '', 'Shopware\\')) {
            return [];
        }

        $methodName = $node->name->name;
        $methodCalledOnType = $scope->getType($node->var);

        foreach ($methodCalledOnType->getObjectClassNames() as $class) {
            $classInfo = $this->reflectionProvider->getClass($class);
            try {
                $methodDetails = $classInfo->getMethod($methodName, $scope);
            } catch (\PHPStan\Reflection\MissingMethodFromReflectionException $e) {
                // Method is not present in class. Nothing to do here for this rule
                continue;
            }

            if (!$methodDetails->isInternal()->yes()) {
                continue;
            }

            $methodOwnerNamespace = $classInfo->getNativeReflection()->getNamespaceName();
            if (false === str_starts_with($methodOwnerNamespace, 'Shopware\\')) {
                continue;
            }
            if (!NamespaceChecker::arePartOfTheSamePackage($scope->getNamespace(), $methodOwnerNamespace)) {
                return [
                    RuleErrorBuilder::message(sprintf('Call of internal method %s::%s Please refrain from using methods which are annotated with @internal in the Shopware 6 repository.', $classInfo->getName(), $methodDetails->getName()))
                        ->identifier('shopware.internal_method_call')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
