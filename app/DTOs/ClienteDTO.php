<?php

namespace App\DTOs;

class ClienteDTO

{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $docid = null,
        public readonly ?string $pnombre = null,
        public readonly ?string $snombre = null,
        public readonly ?string $papellido = null,
        public readonly ?string $spaellido = null,
        public readonly ?int $edad = 0,
        public readonly ?string $telefono = null,
        public readonly ?string $genero = null,
        public readonly ?int $municipio = 0,
        public readonly ?int $departamento = 0,
        public readonly ?\DateTime $phone_updated_at = null,
    ) {}

    public static function fromRequest($request): self
    {
        return new self(
            id: $request['id'] ?? null ,
            docid: $request['docid'] ?? null,
            pnombre: $request['pnombre'] ?? null,
            snombre: $request['snombre'] ?? null,
            papellido: $request['papellido'] ?? null,
            spaellido: $request['spaellido'] ?? null,
            edad: $request['edad'] ?? 0,
            telefono: $request['telefono'] ?? null,
            genero: $request['genero'] ?? null,
            municipio: $request['municipio']['id'] ?? $request['municipio'] ?? 0,
            departamento: $request['departamento']['id'] ?? $request['departamento'] ?? 0,
            phone_updated_at: now(),
        );
    }



    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            docid: $data['docid'] ?? null,
            pnombre: $data['pnombre'] ?? null,
            snombre: $data['snombre'] ?? null,
            papellido: $data['papellido'] ?? null,
            spaellido: $data['spaellido'] ?? null,
            edad: $data['edad'] ?? null,
            telefono: $data['telefono'] ?? null,
            genero: $data['genero'] ?? null,
            municipio: $data['municipio']['id'] ?? $data['municipio'] ?? null,
            departamento: $data['departamento']['id'] ?? $data['departamento'] ?? null,
            phone_updated_at: now(),
        );
    }

    public static function fromUpdate(array $data): self
    {
        return self::fromArray($data);
    }

    public static function fromPhoneUpdate(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            telefono: $data['telefono'] ?? null,
            phone_updated_at: now(),
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'docid' => strtoupper($this->docid),
            'pnombre' => strtoupper($this->pnombre),
            'snombre' => strtoupper($this->snombre),
            'papellido' => strtoupper($this->papellido),
            'spaellido' => strtoupper($this->spaellido),
            'edad' => $this->edad,
            'telefono' => $this->telefono,
            'genero' => $this->genero,
            'municipio' => $this->municipio,
            'departamento' => $this->departamento,
            'phone_updated_at' => $this->phone_updated_at,
        ], fn($value) => $value !== null);
    }
}
