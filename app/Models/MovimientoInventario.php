<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table =
        'movimientos_inventario';

    public const UPDATED_AT = null;

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'tipo_movimiento_id',
        'usuario_id',

        'moneda_id',
        'tipo_cambio_id',

        'costo_unitario_origen',
        'costo_total_origen',
        'costo_unitario_bob',
        'costo_total_bob',
        'costo_promedio_resultante_bob',

        'cambio_disponible',
        'cambio_reservado',
        'saldo_disponible_resultante',
        'saldo_reservado_resultante',

        'tipo_referencia',
        'referencia_id',
        'fecha_movimiento',
        'observacion',
    ];

    protected $casts = [
        'costo_unitario_origen' =>
            'decimal:6',

        'costo_total_origen' =>
            'decimal:2',

        'costo_unitario_bob' =>
            'decimal:6',

        'costo_total_bob' =>
            'decimal:2',

        'costo_promedio_resultante_bob' =>
            'decimal:6',

        'cambio_disponible' =>
            'integer',

        'cambio_reservado' =>
            'integer',

        'saldo_disponible_resultante' =>
            'integer',

        'saldo_reservado_resultante' =>
            'integer',

        'fecha_movimiento' =>
            'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class
        );
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class
        );
    }

    public function tipoMovimiento(): BelongsTo
    {
        return $this->belongsTo(
            TipoMovimientoInventario::class,
            'tipo_movimiento_id'
        );
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(
            Moneda::class,
            'moneda_id'
        );
    }

    public function tipoCambio(): BelongsTo
    {
        return $this->belongsTo(
            TipoCambio::class,
            'tipo_cambio_id'
        );
    }
}
