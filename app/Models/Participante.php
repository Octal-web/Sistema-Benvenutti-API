<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Participante extends Model
{
    protected $table = 'cadastros_participantes';

    protected $guarded = ['id'];

    const CREATED_AT = 'criado';
    const UPDATED_AT = 'modificado';
    
    public function usuario() {
        return $this->belongsTo(Usuario::class);
    }
    
    public function pontos() {
        return $this->hasMany(Ponto::class);
    }
}