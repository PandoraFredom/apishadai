<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrincipalActivo extends Model
{
    protected $table = 'principal_activos';

    protected $fillable = ['nombre', 'concentracion'];

    protected function casts(): array
    {
        return ['concentracion' => 'integer'];
    }

    public function concentracionDetalle(): BelongsTo
    {
        return $this->belongsTo(Concentracion::class, 'concentracion');
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'prod_activo', 'pactivo', 'producto')
            ->withTimestamps();
    }

    public function asociaciones(): HasMany
    {
        return $this->hasMany(ProdActivo::class, 'pactivo');
    }
}
