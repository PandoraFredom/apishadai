<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vistas extends Model
{
    protected $table = 'vistas';
    protected $fillable = ['modulo', 'nombre', 'estado','codigo'];

    public function Estado()
    {
        return $this->belongsTo(VistaEstados::class, 'estado');
    }

    public function Modulo()
    {
        return $this->belongsTo(Modulos::class, 'modulo');
    }
    public function Permisos()
    {
        return $this->hasMany(Permisos::class);
    }
    public function Actions()
    {
        return $this->hasMany(ActionsVistas::class);
    }
    
}
