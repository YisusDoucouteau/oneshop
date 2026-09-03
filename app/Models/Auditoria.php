<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Auditoria extends Model
{
    protected $table = 'auditorias';


    public $timestamps = false;


    protected $fillable = [

        'usuario_id',
        'accion',
        'entidad',
        'entidad_id',
        'datos_anteriores',
        'datos_nuevos',
        'direccion_ip',
        'agente_usuario',
        'ruta',
        'metodo_http',
        'fecha_evento',

    ];


    protected $casts = [

        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha_evento' => 'datetime',

    ];


    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}