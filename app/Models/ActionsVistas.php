<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActionsVistas extends Model
{
    // table
    protected $table = 'actionsvistas';

    //fillable
    protected $fillable = [
        'vista',
        'codigo',
        'nombre',
    ];

    public function Vista()
    {
        return $this->belongsTo(Vistas::class, 'vista', 'id');
    }

    public function Permiso()
    {
        return $this->hasMany(Permisos::class);
    }
}
