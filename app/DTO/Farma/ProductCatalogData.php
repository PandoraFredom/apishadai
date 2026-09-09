<?php

namespace App\DTOs\Farma;

use App\Interfaces\Farma\ProductCatalogRegistryService;

class ProductCatalogData
{
    /** @param array<string, mixed> $attributes */
    public function __construct(public readonly array $attributes) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(
        string $catalog,
        array $validated,
        ProductCatalogRegistryService $registry,
    ): self {
        unset($validated['id']);

        return new self($registry->sanitize($catalog, $validated));
    }
}
