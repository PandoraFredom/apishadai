<?php

namespace App\DTO\Utils;

use UnexpectedValueException;

final readonly class VerseDTO
{
    public function __construct(
        public string $bookId,
        public string $bookName,
        public int $chapter,
        public int $verse,
        public string $text,
    ) {}

    public static function fromApiResponse(array $verse): self
    {
        foreach (['book_id', 'book_name', 'chapter', 'verse', 'text'] as $field) {
            if (! array_key_exists($field, $verse)) {
                throw new UnexpectedValueException("El campo verses.{$field} no existe en la respuesta de BIBLE_API.");
            }
        }

        if (
            ! is_string($verse['book_id'])
            || ! is_string($verse['book_name'])
            || ! is_int($verse['chapter'])
            || ! is_int($verse['verse'])
            || ! is_string($verse['text'])
        ) {
            throw new UnexpectedValueException('La API bíblica retornó un versículo con tipos de datos inválidos.');
        }

        return new self(
            bookId: $verse['book_id'],
            bookName: $verse['book_name'],
            chapter: $verse['chapter'],
            verse: $verse['verse'],
            text: $verse['text'],
        );
    }

    public function toArray(): array
    {
        return [
            'bookId' => $this->bookId,
            'bookName' => $this->bookName,
            'chapter' => $this->chapter,
            'verse' => $this->verse,
            'text' => $this->text,
        ];
    }
}
