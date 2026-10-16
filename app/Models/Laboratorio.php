<?php

namespace App\Models;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class Laboratorio extends Model
{
    use PublishesHeadquarters;

    protected $table = 'laboratorios';

    protected $fillable = [
        'nombre',
        'telefono',
        'direccion',
        'imagen',
    ];
}
