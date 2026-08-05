<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamPresentacion extends Model
{
    protected $table = 'fam_presentacion';

    protected $fillable = ['descripcion'];

    public function familias(): HasMany
    {
        return $this->hasMany(Familia::class, 'presentacion');
    }
}
