<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Distribucion extends Model
{
    protected $table = 'distribucion';

    protected $fillable = ['lote', 'precio', 'isv', 'dto1', 'dto2', 'dto3', 'dto4'];

    protected function casts(): array
    {
        return [
            'lote' => 'integer',
            'precio' => 'decimal:2',
            'isv' => 'boolean',
            'dto1' => 'decimal:2',
            'dto2' => 'decimal:2',
            'dto3' => 'decimal:2',
            'dto4' => 'decimal:2',
        ];
    }

    public function loteDetalle(): BelongsTo
    {
        return $this->belongsTo(Lote::class, 'lote');
    }
}
