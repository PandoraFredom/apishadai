<?php

namespace App\Models;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class DeviceEstado extends Model
{
    use PublishesHeadquarters;

    protected $table = 'device_estado';

    protected $fillable = ['descripcion'];

    public function Device()
    {
        return $this->hasMany(Device::class, 'estado');
    }
}
