<?php

namespace App\Models\Farma;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Concentracion extends Model
{
    use PublishesHeadquarters;

    protected $table = 'concentraciones';

    protected $fillable = ['valor'];

    public function principiosActivos(): HasMany
    {
        return $this->hasMany(PrincipalActivo::class, 'concentracion');
    }
}
