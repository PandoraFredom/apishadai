<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rol',
        'nombre',
        'name',
        'password',
        'email',
        'estado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'name',
        'password',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'name' => 'hashed',
        ];
    }
    /**
     * @inheritDoc
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function Rol()
    {
        return $this->belongsTo(Roles::class, 'rol');
    }
    public function Estado()
    {
        return $this->belongsTo(UsuarioEstado::class, 'estado');
    }

    public function Permisos()
    {
        return $this->hasMany(Permisos::class, 'usuario');
    }

    public function WorkLunch()
    {
        return $this->hasMany(WorkLunch::class);
    }
    //tikets
    public function Tikets()
    {
        return $this->hasMany(tikets::class, 'usuario');
    }
}
