<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipios extends Model
{
    protected $table = 'municipios';

    protected $fillable = [
        'nombre',
        'departamento',
    ];

    public function Departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    public function Cliente()
    {
        return $this->hasMany(Clientes::class, 'municipio', 'id');
    }
}
