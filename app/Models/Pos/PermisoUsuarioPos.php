<?php

namespace App\Models\Pos;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PermisoUsuarioPos extends Model
{
    use PublishesHeadquarters;

    protected $table = 'permisosusuariospos';

    protected $fillable = ['usuario', 'modulo', 'vista', 'actionvista', 'tipo_tiempo', 'lifetime'];

    protected $casts = ['usuario' => 'integer', 'modulo' => 'integer', 'vista' => 'integer', 'actionvista' => 'integer', 'tipo_tiempo' => 'integer', 'lifetime' => 'integer'];

    public function usuarioCuenta(): BelongsTo
    {
        return $this->belongsTo(UsuarioPos::class, 'usuario');
    }

    public function moduloDetalle(): BelongsTo
    {
        return $this->belongsTo(ModuloPos::class, 'modulo');
    }

    public function vistaDetalle(): BelongsTo
    {
        return $this->belongsTo(VistaPos::class, 'vista');
    }

    public function accionDetalle(): BelongsTo
    {
        return $this->belongsTo(BotonPos::class, 'actionvista');
    }
}
