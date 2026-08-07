<?php

namespace App\Models\Farma;

use App\Models\Stocks;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transferencia extends Model
{
    protected $table = 'transferencias';

    protected $fillable = [
        'tipo', 'stock_de', 'stock_para', 'usuario_envia', 'usuario_recibe',
        'items', 'subtotal', 'isvtotal', 'total', 'estado', 'estado_recepcion',
        'enviado_at', 'recibido_at',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => 'integer',
            'stock_de' => 'integer',
            'stock_para' => 'integer',
            'usuario_envia' => 'integer',
            'usuario_recibe' => 'integer',
            'items' => 'integer',
            'subtotal' => 'decimal:2',
            'isvtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'estado' => 'integer',
            'estado_recepcion' => 'integer',
            'enviado_at' => 'datetime',
            'recibido_at' => 'datetime',
        ];
    }

    public function tipoDetalle(): BelongsTo
    {
        return $this->belongsTo(TransferenciaTipo::class, 'tipo');
    }

    public function stockOrigen(): BelongsTo
    {
        return $this->belongsTo(Stocks::class, 'stock_de');
    }

    public function stockDestino(): BelongsTo
    {
        return $this->belongsTo(Stocks::class, 'stock_para');
    }

    public function usuarioEnvia(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_envia');
    }

    public function usuarioRecibe(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_recibe');
    }

    public function estadoDetalle(): BelongsTo
    {
        return $this->belongsTo(TransferenciaEstado::class, 'estado');
    }

    public function estadoRecepcionDetalle(): BelongsTo
    {
        return $this->belongsTo(TransferenciaEstado::class, 'estado_recepcion');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(TransferenciaDetalle::class, 'transferencia_id');
    }
}
