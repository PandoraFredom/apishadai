<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsuarioEstado extends Model
{
    protected $table = "user_estados";
    protected $fillable = [
        'descripcion',
    ];


    public function Usuario(){
        return $this->hasMany(User::class);
    }
}
