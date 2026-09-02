<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notificacion extends Model
{
    protected $table = 'notificacions';


    protected $fillable = [

        'usuario_id',

        'tipo',

        'titulo',

        'mensaje',

        'referencia_tipo',

        'referencia_id',

        'canal',

        'leido',

        'fecha_lectura',

        'resultado',

    ];



    protected $casts = [

        'leido' =>
            'boolean',

        'fecha_lectura' =>
            'datetime',

    ];



    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }
}