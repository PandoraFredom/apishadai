<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'departamentos';

    protected $fillable = [
        'nombre',
    ];

    public function Municipios()
    {
        return $this->hasMany(Municipios::class, 'departamento', 'id');
    }
    public function Cliente()
    {
        return $this->hasMany(Clientes::class, 'departamento', 'id');
    }



}
