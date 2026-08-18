<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EstadoEquipo extends Model
{
    protected $table = 'estados_equipos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'es_final',
        'orden',
        'activo',
    ];

    protected $casts = [
            'es_final' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];

    public function equipos(): HasMany
    {
        return $this->hasMany(
            Equipo::class,
            'estado_actual_id'
        );
    }

    public function transicionesOrigen(): HasMany
    {
        return $this->hasMany(
            TransicionEstadoEquipo::class,
            'estado_origen_id'
        );
    }

    public function transicionesDestino(): HasMany
    {
        return $this->hasMany(
            TransicionEstadoEquipo::class,
            'estado_destino_id'
        );
    }
}