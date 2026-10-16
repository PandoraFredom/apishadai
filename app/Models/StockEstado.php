<?php

namespace App\Models;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class StockEstado extends Model
{
    use PublishesHeadquarters;

    protected $table = 'stock_estado';

    protected $fillable = [
        'descripcion',
    ];

    public function Stocks()
    {
        return $this->hasMany(Stocks::class, 'estado');
    }
}
