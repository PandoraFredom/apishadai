<?php

namespace App\DTO\Utils;

use UnexpectedValueException;
use function is_array;
use function is_int;
use function is_string;

final readonly class BibliaDTO
{
    /**
     * @param  list<VerseDTO>  $verses
     */
    public function __construct(
        public string $reference,
        public string $version,
        public int $count,
        public array $verses,
    ) {}

    public static function fromApiResponse(array $payload): self
    {
        foreach (['reference', 'version', 'count', 'verses'] as $field) {
            if (! array_key_exists($field, $payload)) {
                throw new UnexpectedValueException("El campo {$field} no existe en la respuesta de BIBLE_API.");
            }
        }

        if (
            ! is_string($payload['reference'])
            || ! is_string($payload['version'])
            || ! is_int($payload['count'])
            || ! is_array($payload['verses'])
            || ! array_is_list($payload['verses'])
        ) {
            throw new UnexpectedValueException('La API bíblica retornó una estructura inválida.');
        }

        $verses = array_map(
            function (mixed $verse): VerseDTO {
                if (! is_array($verse)) {
                    throw new UnexpectedValueException('La API bíblica retornó un versículo inválido.');
                }

                return VerseDTO::fromApiResponse($verse);
            },
            $payload['verses'],
        );

        if ($payload['count'] !== count($verses)) {
            throw new UnexpectedValueException('El total de versículos no coincide con el campo count de BIBLE_API.');
        }

        return new self(
            reference: $payload['reference'],
            version: $payload['version'],
            count: $payload['count'],
            verses: $verses,
        );
    }

    public function toArray(): array
    {
        return [
            'reference' => $this->reference,
            'version' => $this->version,
            'count' => $this->count,
            'verses' => array_map(
                fn (VerseDTO $verse): array => $verse->toArray(),
                $this->verses,
            ),
        ];
    }
}
