<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class tikets extends Model
{
    protected $table = 'tikets';


    protected $fillable = [
        'promocion',
        'cliente',
        'ntiket',
        'usuario',
        'stock',
    ];

    //promocion
    public function Promocion()
    {
        return $this->belongsTo(Promociones::class, 'promocion');
    }

    //cliente
    public function Cliente()
    {
        return $this->belongsTo(Clientes::class, 'cliente');
    }
    //usuario
    public function Usuario()
    {
        return $this->belongsTo(User::class, 'usuario');
    }
    //stock
    public function Stock()
    {
        return $this->belongsTo(Stocks::class, 'stock');
    }
}
