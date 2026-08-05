<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorasLab extends Model
{
    protected $table = 'horas_lab';

    protected $fillable = [
        'usuario',
        'horas_lab',
        'horas_lunch',
    ];

    protected function casts(): array
    {
        return [
            'usuario' => 'integer',
            'horas_lab' => 'integer',
            'horas_lunch' => 'integer',
        ];
    }

    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario');
    }
}
