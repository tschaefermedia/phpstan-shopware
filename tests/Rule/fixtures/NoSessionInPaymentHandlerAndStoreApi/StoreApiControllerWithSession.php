<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\Fixtures\NoSessionInPaymentHandlerAndStoreApi;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['store-api']])]
class StoreApiControllerWithSession
{
    public function __construct(
        private readonly SessionInterface $session,
    ) {}

    #[Route(path: '/store-api/test', name: 'store-api.test', methods: ['GET'])]
    public function testAction(): void
    {
        $this->session->get('foo');
    }
}
