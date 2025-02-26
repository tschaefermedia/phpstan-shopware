<?php

declare(strict_types=1);

namespace Tests\PreferRouteEventListenerRule;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, method: 'onRequest')]
class Listener
{
    public function onRequest(RequestEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'foo') {
            return;
        }
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onFooRequest(RequestEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'foo') {
            return;
        }
    }
}
