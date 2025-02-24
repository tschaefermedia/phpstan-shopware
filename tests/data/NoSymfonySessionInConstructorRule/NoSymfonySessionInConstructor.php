<?php

declare(strict_types=1);

namespace YourNamespace\Tests\Rule\Data;

use Symfony\Component\HttpFoundation\Session\Session;

class ClassWithSessionInConstructor
{
    private Session $session;

    public function __construct(
        Session $session,
    ) {
        $session->get('test');
    }
}

class ClassSessionInMethod
{
    public function __construct(
        private Session $session,
    ) {}

    public function someMethod(): void
    {
        $this->session->get('test');
    }
}
