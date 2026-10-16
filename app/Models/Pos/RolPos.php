<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class RolPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'rolespos';

    protected $fillable = ['nombre', 'nivel', 'activo'];

    // The old JSON column is retained only for historical synchronization compatibility.
    protected $casts = ['nivel' => 'integer', 'permisos' => 'array', 'activo' => 'boolean'];

    protected $attributes = ['permisos' => '[]'];
}
