<?php

declare(strict_types=1);

namespace Tests\PreferRouteEventListenerRule;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Subscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onRequest',
            KernelEvents::RESPONSE => [
                'onFoo',
            ],
            KernelEvents::CONTROLLER => [
                ['onController', 10],
            ],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'foo') {
            return;
        }
    }

    public function onFoo(RequestEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'foo') {
            return;
        }
    }

    public function onController(RequestEvent $event): void
    {
        if ($event->getRequest()->attributes->get('_route') !== 'foo') {
            return;
        }
    }
}
