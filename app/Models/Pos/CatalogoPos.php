<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class CatalogoPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'catalogospos';

    protected $hidden = ['descuento_global_codigo'];

    protected $fillable = ['stock_id', 'tipo', 'codigo', 'datos', 'activo'];

    protected $casts = ['stock_id' => 'integer', 'rol_id' => 'integer', 'datos' => 'array', 'activo' => 'boolean'];
}
