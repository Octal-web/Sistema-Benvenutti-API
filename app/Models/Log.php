<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'usuarios_logs';
    
    protected $guarded = ['id'];

    const CREATED_AT = 'criado';
    const UPDATED_AT = null;

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
