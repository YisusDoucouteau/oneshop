<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    public const UPDATED_AT = null;

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'tipo_movimiento_id',
        'usuario_id',
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
            'cambio_disponible' => 'integer',
            'cambio_reservado' => 'integer',
            'saldo_disponible_resultante' => 'integer',
'saldo_reservado_resultante' => 'integer',
            'fecha_movimiento' => 'datetime',
        ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
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
        return $this->belongsTo(User::class);
    }
}