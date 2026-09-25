<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoAjusteGarantia extends Model
{
    protected $table = 'movimientos_ajustes_garantia';

    protected $fillable = [
        'cambio_equipo_id',
        'tipo_movimiento',
        'metodo_pago_id',
        'monto',
        'fecha_movimiento',
        'referencia',
        'comprobante',
        'estado',
        'registrado_por_id',
        'verificado_por_id',
        'fecha_verificacion',
        'motivo_rechazo',
        'observacion',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
        'fecha_verificacion' => 'datetime',
    ];

    public function cambioEquipo(): BelongsTo
    {
        return $this->belongsTo(
            CambioEquipo::class,
            'cambio_equipo_id'
        );
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(
            MetodoPago::class,
            'metodo_pago_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }

    public function verificadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verificado_por_id'
        );
    }
}