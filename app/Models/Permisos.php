<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permisos extends Model
{
    protected $table = 'permisos';
    protected $fillable = [
        'usuario',
        'modulo',
        'vista',
        'lifetime',
        'actionvista',
        'tipo_tiempo',
    ];


    public function Usuario()
    {
        return $this->belongsTo(User::class, 'usuario');
    }

    public function Modulo()
    {
        return $this->belongsTo(Modulos::class, 'modulo');
    }
    public function Vista()
    {
        return $this->belongsTo(Vistas::class, 'vista');
    }

    public function ActionVista()
    {
        return $this->belongsTo(ActionsVistas::class, 'actionvista');
    }
    public function TipoTiempo()
    {
        return $this->belongsTo(TipoTiempo::class, 'tipo_tiempo');
    }
}
