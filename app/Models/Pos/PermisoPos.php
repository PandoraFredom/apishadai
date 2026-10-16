<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermisoPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'permisospos';

    protected $fillable = ['rol_id', 'modulo_id', 'vista_id', 'boton_id', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function boton(): BelongsTo
    {
        return $this->belongsTo(BotonPos::class, 'boton_id');
    }
}
