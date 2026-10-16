<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BotonPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'botonespos';

    protected $fillable = ['vista_id', 'codigo', 'nombre', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function vista(): BelongsTo
    {
        return $this->belongsTo(VistaPos::class, 'vista_id');
    }
}
