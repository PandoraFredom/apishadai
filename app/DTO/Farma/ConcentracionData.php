<?php

namespace App\DTO\Farma;

class ConcentracionData
{
    /** @param array<string, mixed> $attributes */
    public function __construct(public readonly array $attributes) {}

    /** @param array<string, mixed> $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(self::sanitize($validated));
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function sanitize(array $data): array
    {
        $sanitized = [];

        foreach (['valor'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if ($value === null) {
                $sanitized[$field] = null;

                continue;
            }

            $value = strip_tags((string) $value);
            $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
            $sanitized[$field] = trim($value);
        }

        return $sanitized;
    }
}
