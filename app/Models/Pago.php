<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    protected $table = 'pagos';

    protected $fillable = [
        'reserva_id',
        'venta_id',
        'metodo_pago_id',
        'monto',
        'fecha_pago',
        'referencia',
        'estado',
        'registrado_por_id',
        'verificado_por_id',
        'fecha_verificacion',
        'motivo_rechazo',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_pago' => 'datetime',
            'fecha_verificacion' => 'datetime',
        ];
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
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