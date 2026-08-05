<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompraTipo extends Model
{
    protected $table = 'compra_tipo';

    protected $primaryKey = 'int';

    protected $fillable = ['descripcion'];

    protected function casts(): array
    {
        return ['int' => 'integer'];
    }

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class, 'tipo', 'int');
    }
}
