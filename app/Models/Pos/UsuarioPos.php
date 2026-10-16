<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'usuariospos';

    protected $fillable = ['rol_id', 'nombre', 'vendedor', 'usuario_lookup', 'credenciales', 'activo'];

    protected $casts = ['rol_id' => 'integer', 'credenciales' => 'array', 'activo' => 'boolean'];

    protected $hidden = ['usuario_lookup', 'credenciales'];

    public function rol(): BelongsTo
    {
        return $this->belongsTo(RolPos::class, 'rol_id');
    }
}
