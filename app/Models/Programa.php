<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'dados_programa';

    protected $guarded = ['id'];

    const CREATED_AT = 'criado';
    const UPDATED_AT = 'modificado';
}