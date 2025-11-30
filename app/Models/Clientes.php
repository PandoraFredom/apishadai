<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Clientes extends Model
{
    protected $table = "clientes";
    protected $fillable = [
        'docid',
        'pnombre',
        'snombre',
        'papellido',
        'spaellido',
        'edad',
        'telefono',
        'genero',
        'municipio',
        'departamento',
    ];

    public function Municipio()
    {
        return $this->belongsTo(Municipios::class, 'municipio', 'id');
    }
    public function Departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento', 'id');
    }

    // tikets
    public function Tiket()
    {
        return $this->hasMany(tikets::class, 'cliente', 'id');
    }

}
