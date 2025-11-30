<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuloEstados extends Model
{
    protected $table = "modulo_estados";
    protected $fillable = [
        'descripcion',
    ];

    public function Modulo()
    {
        return $this->hasMany(Modulos::class);
    }
}
