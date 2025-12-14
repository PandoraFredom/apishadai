<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stocks extends Model
{
    protected $table = 'stocks';
    protected $fillable =
    [
        'descripcion',
        'telefono',
        'ubicacion',
    ];

    public function Device()
    {
        return $this->hasMany(Device::class, 'stock');
    }
    public function Tikets()
    {
        return $this->hasMany(tikets::class, 'stock');
    }
}
