<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Rule;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route as RouteAnnotation;
use Symfony\Component\Routing\Attribute\Route as RouteAttribute;

/**
 * @implements Rule<MethodCall>
 */
class NoSessionInPaymentHandlerAndStoreApiRule implements Rule
{
    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $type = $scope->getType($node->var);

        // @phpstan-ignore-next-line
        if (!$type instanceof ObjectType) {
            return [];
        }

        if (!$type->isInstanceOf(SessionInterface::class)->yes()) {
            return [];
        }

        $classReflection = $scope->getClassReflection();

        if ($classReflection === null) {
            return [];
        }

        // Check if class extends AbstractPaymentHandler
        if ($classReflection->isSubclassOf(AbstractPaymentHandler::class)) {
            return [
                RuleErrorBuilder::message('Session usage is not allowed in payment handlers.')
                    ->identifier('shopware.sessionUsageInPaymentHandler')
                    ->build(),
            ];
        }

        // Check for Store-API route attribute
        $nativeReflection = $classReflection->getNativeReflection();
        $attributes = array_merge(
            $nativeReflection->getAttributes(RouteAnnotation::class),
            $nativeReflection->getAttributes(RouteAttribute::class),
        );

        foreach ($attributes as $attribute) {
            /** @var array{defaults?: array{_routeScope?: array<string>}} $args */
            $args = $attribute->getArguments();
            if (isset($args['defaults']['_routeScope']) && in_array('store-api', (array) $args['defaults']['_routeScope'], true)) {
                return [
                    RuleErrorBuilder::message('Session usage is not allowed in Store-API controllers.')
                        ->identifier('shopware.sessionUsageInStoreApi')
                        ->build(),
                ];
            }
        }

        return [];
    }
}
