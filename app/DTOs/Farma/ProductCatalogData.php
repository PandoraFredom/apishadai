<?php

namespace App\DTOs\Farma;

use App\Services\Farma\ProductCatalogRegistry;

class ProductCatalogData
{
    /** @param array<string, mixed> $attributes */
    public function __construct(public readonly array $attributes) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(
        string $catalog,
        array $validated,
        ProductCatalogRegistry $registry,
    ): self {
        unset($validated['id']);

        return new self($registry->sanitize($catalog, $validated));
    }
}
