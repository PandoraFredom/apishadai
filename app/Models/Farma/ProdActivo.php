<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProdActivo extends Model
{
    protected $table = 'prod_activo';

    protected $fillable = ['producto', 'pactivo'];

    protected function casts(): array
    {
        return ['producto' => 'integer', 'pactivo' => 'integer'];
    }

    public function productoDetalle(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto');
    }

    public function principioActivo(): BelongsTo
    {
        return $this->belongsTo(PrincipalActivo::class, 'pactivo');
    }
}
