<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceEstado extends Model
{
    protected $table = 'device_estado';
    protected $fillable = ['descripcion'];


    public function Device()
    {
        return $this->hasMany(Device::class, 'estado');
    }
}
