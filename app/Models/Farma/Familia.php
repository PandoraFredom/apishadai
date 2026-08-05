<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Familia extends Model
{
    protected $table = 'familia';

    protected $fillable = ['presentacion', 'administracion', 'descripcion'];

    protected function casts(): array
    {
        return ['presentacion' => 'integer', 'administracion' => 'integer'];
    }

    public function presentacionDetalle(): BelongsTo
    {
        return $this->belongsTo(FamPresentacion::class, 'presentacion');
    }

    public function administracionDetalle(): BelongsTo
    {
        return $this->belongsTo(FamAdministracion::class, 'administracion');
    }

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'familia');
    }
}
