<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $fillable = [
        'numero',
        'cliente_id',
        'vendedor_id',
        'reserva_id',
        'fecha_venta',
        'subtotal',
        'descuento_total',
        'total',
        'estado',
        'anulado_por_id',
        'fecha_anulacion',
        'motivo_anulacion',
        'observacion',
    ];

    protected $casts = [
            'fecha_venta' => 'datetime',
            'subtotal' => 'decimal:2',
            'descuento_total' => 'decimal:2',
            'total' => 'decimal:2',
            'fecha_anulacion' => 'datetime',
        ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'vendedor_id'
        );
    }

    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'anulado_por_id'
        );
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleVenta::class,
            'venta_id'
        );
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(
            Pago::class,
            'venta_id'
        );
    }
}