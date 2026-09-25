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

        'valor_original_snapshot',
        'valor_reemplazo_snapshot',
        'diferencia_snapshot',
        'moneda_ajuste',
        'tipo_ajuste',
        'estado_ajuste',
    ];

    protected $casts = [
        'fecha_cambio' => 'datetime',

        'valor_original_snapshot' => 'decimal:2',
        'valor_reemplazo_snapshot' => 'decimal:2',
        'diferencia_snapshot' => 'decimal:2',
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