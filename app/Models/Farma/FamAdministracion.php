<?php

namespace App\Models\Farma;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamAdministracion extends Model
{
    protected $table = 'fam_administracion';

    protected $fillable = ['descripcion'];

    public function familias(): HasMany
    {
        return $this->hasMany(Familia::class, 'administracion');
    }
}
