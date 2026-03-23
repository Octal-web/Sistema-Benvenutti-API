<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable, HasFactory;

    protected $table = 'usuarios';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'nome',
        'email',
        'password',
        'funcao',
        'ativo'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var string[]
     */
    protected $hidden = [
        'password',
    ];

    const CREATED_AT = 'criado';
    const UPDATED_AT = 'modificado';

    public function participante() {
        return $this->hasOne(Participante::class);
    }

    public function tokens() {
        return $this->hasMany(Token::class);
    }

    public function logs() {
        return $this->hasMany(Log::class);
    }

    public function isParticipante()
    {
        return $this->funcao === 'participante';
    }

    public function isAdmin()
    {
        return $this->funcao === 'administrador';
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}