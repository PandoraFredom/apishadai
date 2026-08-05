<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdUnidad extends Model
{
    protected $table = 'prod_unidades';

    protected $fillable = ['abreviatura_c', 'abreviatura_v', 'cantidad_c', 'cantidad_v'];

    protected function casts(): array
    {
        return ['cantidad_c' => 'integer', 'cantidad_v' => 'integer'];
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'unidad');
    }
}
