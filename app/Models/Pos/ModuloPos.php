<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuloPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'modulospos';

    protected $fillable = ['codigo', 'nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function vistas(): HasMany
    {
        return $this->hasMany(VistaPos::class, 'modulo_id');
    }
}
