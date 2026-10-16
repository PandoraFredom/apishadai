<?php

namespace App\Models;

use App\Models\Concerns\PublishesHeadquarters;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use PublishesHeadquarters;

    protected $table = 'devices';

    protected $fillable = [
        'ip',
        'ip2',
        'displayname',
        'name',
        'stock',
        'estado',
        'hq_pairing_uuid',
    ];

    protected $casts = [
        'stock' => 'integer',
        'estado' => 'integer',
    ];

    protected $hidden = [
        'ip',
        'ip2',
        'name',
    ];

    public function Estado()
    {
        return $this->belongsTo(DeviceEstado::class, 'estado');
    }

    public function Stock()
    {
        return $this->belongsTo(Stocks::class, 'stock');
    }

    public function WorkLunch()
    {
        return $this->hasMany(WorkLunch::class);
    }
}
