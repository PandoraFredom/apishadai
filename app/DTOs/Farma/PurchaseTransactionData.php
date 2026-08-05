<?php

namespace App\DTOs\Farma;

class PurchaseTransactionData
{
    public function __construct(
        public readonly int $compra,
        public readonly int $tipo,
        public readonly ?string $nro,
        public readonly string $valor,
        public readonly ?string $img,
    ) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            compra: (int) $validated['compra'],
            tipo: (int) $validated['tipo'],
            nro: filled($validated['nro'] ?? null) ? self::text((string) $validated['nro']) : null,
            valor: number_format((float) $validated['valor'], 2, '.', ''),
            img: isset($validated['img']) ? trim((string) $validated['img']) : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $data = ['compra' => $this->compra, 'tipo' => $this->tipo, 'nro' => $this->nro, 'valor' => $this->valor];

        if ($this->img !== null) {
            $data['img'] = $this->img;
        }

        return $data;
    }

    private static function text(string $value): string
    {
        return trim(strip_tags($value));
    }
}
