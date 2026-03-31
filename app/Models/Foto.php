<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Foto extends Model
{
    protected $table = 'fotos';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'imagem',
        'ordem',
        'visivel',
        'galeria_id'
    ];

    protected $guarded = ['id'];

    const CREATED_AT = 'criado';
    const UPDATED_AT = 'modificado';

    public function galeria()
    {
        return $this->belongsTo(Galeria::class);
    }
}
