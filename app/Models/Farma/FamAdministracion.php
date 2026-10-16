<?php

namespace App\Models\Farma;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamAdministracion extends Model
{
    use PublishesHeadquarters;

    protected $table = 'fam_administracion';

    protected $fillable = ['descripcion'];

    public function familia(): HasMany
    {
        return $this->hasMany(Familia::class, 'administracion');
    }
}
