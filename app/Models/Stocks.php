<?php

namespace App\Models;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class Stocks extends Model
{
    use PublishesHeadquarters;

    protected $table = 'stocks';

    protected $fillable =
        [
            'descripcion',
            'telefono',
            'ubicacion',
            'estado',
        ];

    protected $casts = [
        'estado' => 'integer',
    ];

    public function Estado()
    {
        return $this->belongsTo(StockEstado::class, 'estado');
    }

    public function Device()
    {
        return $this->hasMany(Device::class, 'stock');
    }

    public function Tikets()
    {
        return $this->hasMany(tikets::class, 'stock');
    }
}
