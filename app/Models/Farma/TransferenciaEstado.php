<?php

namespace App\Models\Farma;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransferenciaEstado extends Model
{
    use PublishesHeadquarters;

    protected $table = 'transferencia_estado';

    protected $fillable = ['descripcion'];

    public function transferencias(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'estado');
    }

    public function transferenciasRecepcion(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'estado_recepcion');
    }
}
