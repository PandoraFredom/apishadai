<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferenciaTipo extends Model
{
    protected $table = 'transferencias_tipo';

    protected $fillable = ['descripcion'];

    public function transferencias(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'tipo');
    }
}
