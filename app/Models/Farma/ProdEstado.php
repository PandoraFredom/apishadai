<?php

namespace App\Models\Farma;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProdEstado extends Model
{
    use PublishesHeadquarters;

    protected $table = 'prod_estado';

    protected $fillable = ['descripcion'];

    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'estado');
    }
}
