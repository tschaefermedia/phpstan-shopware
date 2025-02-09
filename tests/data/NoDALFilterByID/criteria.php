<?php

declare(strict_types=1);

use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

$criteria = new Criteria();
$criteria->addFilter(new EqualsFilter('id', '12345'));
