<?php

declare(strict_types=1);

namespace YourNamespace\Tests\Rule\Data;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

class ClassWithHttpClass
{
    private HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
    ) {
        $this->httpClient = $httpClient->withOptions([
            'base_uri' => '...',
            'timeout' => 1,
            'connect_timeout' => 1,
            'headers' => [
                'User-Agent' => 'Abc/Shopware6 1.0',
            ],
        ]);
    }
}
