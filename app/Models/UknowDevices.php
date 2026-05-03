<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UknowDevices extends Model
{
    protected $table = 'uknowdevices';

    protected $fillable = [
        'ip',
        'name',
    ];
}
