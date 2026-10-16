<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VistaPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'vistaspos';

    protected $fillable = ['modulo_id', 'codigo', 'nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function botones(): HasMany
    {
        return $this->hasMany(BotonPos::class, 'vista_id');
    }
}
