<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promociones extends Model
{
    protected $table = 'promociones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'impresiones',
        'valor',
    ];

    protected $casts = [
        'impresiones' => 'integer',
        'valor' => 'float',
    ];

    public function Estado()
    {
        return $this->belongsTo(PromoEstado::class, 'estado');
    }
    public function Tikets()
    {
        return $this->hasMany(tikets::class, 'promocion');
    }
}
