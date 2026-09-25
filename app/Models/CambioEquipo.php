<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CambioEquipo extends Model
{
    protected $table = 'cambios_equipos';

    protected $fillable = [
        'caso_garantia_id',
        'equipo_saliente_id',
        'equipo_entrante_id',
        'autorizado_por_id',
        'fecha_cambio',
        'motivo',
        'observacion',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',
    ];

    public function casoGarantia(): BelongsTo
    {
        return $this->belongsTo(
            CasoGarantia::class,
            'caso_garantia_id'
        );
    }

    public function equipoSaliente(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_saliente_id'
        );
    }

    public function equipoEntrante(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_entrante_id'
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