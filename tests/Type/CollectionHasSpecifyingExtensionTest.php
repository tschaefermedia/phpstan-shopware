<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

class CollectionHasSpecifyingExtensionTest extends TypeInferenceTestCase
{
    public function testCollectionHas(): void
    {
        foreach (static::gatherAssertTypes(__DIR__ . '/../data/CollectionHasSpecifyingExtension/collection_has.php') as $args) {
            // because of the autoload issue we can not use data providers as phpstan does itself,
            // therefore we need to rely on this hacks
            $assertType = array_shift($args);
            $file = array_shift($args);

            $this->assertFileAsserts($assertType, $file, ...$args);
        }
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../data/CollectionHasSpecifyingExtension/extension.neon',
        ];
    }
}
