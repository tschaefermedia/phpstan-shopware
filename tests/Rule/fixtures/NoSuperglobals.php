<?php

declare(strict_types=1);

namespace Shopware\PHPStan\Tests\Rule\Fixtures;

class NoSuperglobals
{
    public function test(): void
    {
        $get = $_GET['test'];
        $post = $_POST['test'];
        $files = $_FILES['test'];
        $request = $_REQUEST['test'];

        // These should not trigger errors
        $normalVar = 'test';
        $anotherVar = $normalVar;
    }
}
