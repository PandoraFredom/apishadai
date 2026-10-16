<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class Clientepos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'clientepos';

    protected $fillable = ['nombre', 'rtn', 'telefono', 'direccion', 'tipo_documento', 'fecha_nacimiento'];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date:Y-m-d'];
    }
}
