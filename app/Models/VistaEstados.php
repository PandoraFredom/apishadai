<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VistaEstados extends Model
{
    protected $table = 'vista_estados';
    protected $fillable = ['descripcion'];


    public function Vista()
    {
        return $this->hasMany(Vistas::class);
    }
   
}