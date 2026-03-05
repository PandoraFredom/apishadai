<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockEstado extends Model
{
    protected $table = 'stock_estado';

    protected $fillable = [
        'descripcion',
    ];

    public function Stocks()
    {
        return $this->hasMany(Stocks::class, 'estado');
    }
}
