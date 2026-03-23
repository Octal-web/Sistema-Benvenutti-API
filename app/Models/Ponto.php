<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ponto extends Model
{
    protected $table = 'pontos';

    protected $guarded = ['id'];

    const CREATED_AT = 'criado';
    const UPDATED_AT = 'modificado';
    
    public function participantes() {
        return $this->belongsTo(Participante::class);
    }
}