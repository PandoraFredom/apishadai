<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoTiempo extends Model
{
    protected $table = 'tipos_tiempo';
    protected $fillable = [
        'nombre',
        'cantidad',
        'unidad',
    ];

    public function Permisos()
    {
        return $this->hasMany(Permisos::class);
    }
}
