<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClienteUbicacion extends Model
{
    protected $table = 'cliente_ubicacion';

    protected $fillable = [
        'departamento',
        'municipio',
    ];


    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento', 'id');
    }
    public function municipio()
    {
        return $this->belongsTo(Municipios::class, 'municipio', 'id');
    }
    public function cliente()
    {
        return $this->hasMany(Clientes::class, 'id', 'cliente');
    }
}
