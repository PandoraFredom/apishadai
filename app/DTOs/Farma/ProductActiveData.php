<?php

namespace App\DTOs\Farma;

class ProductActiveData
{
    public function __construct(
        public readonly int $producto,
        public readonly int $pactivo,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            producto: (int) $validated['producto'],
            pactivo: (int) $validated['pactivo'],
        );
    }

    /** @return array{producto: int, pactivo: int} */
    public function toArray(): array
    {
        return ['producto' => $this->producto, 'pactivo' => $this->pactivo];
    }
}
