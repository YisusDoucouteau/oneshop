<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialEstadoEquipo extends Model
{
    protected $table = 'historial_estados_equipos';

    public const UPDATED_AT = null;

    protected $fillable = [
        'equipo_id',
        'estado_origen_id',
        'estado_destino_id',
        'usuario_id',
        'autorizado_por_id',
        'fecha_cambio',
        'motivo',
        'observacion',
    ];

    protected $casts = [
            'fecha_cambio' => 'datetime',
        ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function estadoOrigen(): BelongsTo
    {
        return $this->belongsTo(
            EstadoEquipo::class,
            'estado_origen_id'
        );
    }

    public function estadoDestino(): BelongsTo
    {
        return $this->belongsTo(
            EstadoEquipo::class,
            'estado_destino_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }

    public function autorizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'autorizado_por_id'
        );
    }
    
}