<?php

namespace App\Interfaces\Farma;

use Illuminate\Database\Eloquent\Model;

interface ProductCatalogRegistryService
{
    public function names(): array;
    public function has(string $catalog): bool;
    public function model(string $catalog): Model;
    public function relations(string $catalog): array;
    public function rules(string $catalog, ?int $id = null, bool $updating = false, array $input = []): array;
    public function sanitize(string $catalog, array $data): array;
}
