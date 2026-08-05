<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concentracion extends Model
{
    protected $table = 'concentraciones';

    protected $fillable = ['valor'];

    public function principiosActivos(): HasMany
    {
        return $this->hasMany(PrincipalActivo::class, 'concentracion');
    }
}
