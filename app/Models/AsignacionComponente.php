<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionComponente extends Model
{
    protected $table = 'asignaciones_componentes';

    protected $fillable = [
        'equipo_id',
        'producto_id',
        'almacen_id',
        'movimiento_salida_id',
        'movimiento_retorno_id',
        'asignado_por_id',
        'retirado_por_id',
        'cantidad',
        'costo_unitario',
        'fecha_asignacion',
        'fecha_retiro',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'costo_unitario' => 'decimal:2',
            'fecha_asignacion' => 'datetime',
            'fecha_retiro' => 'datetime',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function movimientoSalida(): BelongsTo
    {
        return $this->belongsTo(
            MovimientoInventario::class,
            'movimiento_salida_id'
        );
    }

    public function movimientoRetorno(): BelongsTo
    {
        return $this->belongsTo(
            MovimientoInventario::class,
            'movimiento_retorno_id'
        );
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asignado_por_id');
    }

    public function retiradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retirado_por_id');
    }
}