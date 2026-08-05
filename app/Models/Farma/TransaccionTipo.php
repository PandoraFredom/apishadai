<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransaccionTipo extends Model
{
    protected $table = 'transc_tipo';

    protected $fillable = ['descripcion'];

    public function transacciones(): HasMany
    {
        return $this->hasMany(CompraTransaccion::class, 'tipo');
    }
}
