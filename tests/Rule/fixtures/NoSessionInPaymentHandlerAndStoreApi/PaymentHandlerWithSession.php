<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\Fixtures\NoSessionInPaymentHandlerAndStoreApi;

use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AbstractPaymentHandler;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\PaymentHandlerType;
use Shopware\Core\Checkout\Payment\Cart\PaymentTransactionStruct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PaymentHandlerWithSession extends AbstractPaymentHandler implements PaymentHandlerInterface
{
    public function __construct(
        private readonly SessionInterface $session,
    ) {}

    public function pay(Request $request, PaymentTransactionStruct $transaction, Context $context, ?Struct $validateStruct = null): ?RedirectResponse
    {
        $this->session->get('foo');

        return null;
    }

    public function supports(PaymentHandlerType $type, string $paymentMethodId, Context $context): bool
    {
        return true;
    }
}
