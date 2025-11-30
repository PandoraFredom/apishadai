<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    protected $table = "roles";
    protected $fillable = ['descripcion'];



    public function Usuario()
    {
        return $this->hasMany(User::class);
    }
}
