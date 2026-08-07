<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferenciaDetalle extends Model
{
    protected $table = 'trasferencia_detalle';

    protected $fillable = ['transferencia_id', 'lote', 'cantidad', 'subtotal', 'isv', 'total'];

    protected function casts(): array
    {
        return [
            'transferencia_id' => 'integer',
            'lote' => 'integer',
            'cantidad' => 'integer',
            'subtotal' => 'decimal:2',
            'isv' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function transferencia(): BelongsTo
    {
        return $this->belongsTo(Transferencia::class, 'transferencia_id');
    }

    public function loteDetalle(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote');
    }
}
