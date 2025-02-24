<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\Fixtures;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

class NoEntityRepositoryInLoop
{
    private EntityRepository $repository;

    public function __construct(EntityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function badForLoop(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->repository->search(/* some criteria */);
        }
    }

    public function badForeachLoop(): void
    {
        $items = ['a', 'b', 'c'];
        foreach ($items as $item) {
            $this->repository->search(/* some criteria */);
        }
    }

    public function goodUsage(): void
    {
        // This is fine, not in a loop
        $this->repository->search(/* some criteria */);
    }
}
