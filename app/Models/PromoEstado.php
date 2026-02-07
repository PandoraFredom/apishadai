<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoEstado extends Model
{
    protected $table = 'promoestado';

    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    public function promociones()
    {
        return $this->hasMany(Promociones::class, 'estado');
    }
}
