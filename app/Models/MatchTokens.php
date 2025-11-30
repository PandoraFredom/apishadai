<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchTokens extends Model
{
    protected $table = 'matchtokens';

    protected $fillable = [
        'usuario',
        'device',
        'token',
    ];

}
