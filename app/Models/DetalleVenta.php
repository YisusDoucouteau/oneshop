<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    protected $table = 'detalles_ventas';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'equipo_id',
        'cantidad',
        'precio_lista_snapshot',
        'descuento_unitario',
        'precio_unitario',
        'costo_snapshot',
        'subtotal',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_lista_snapshot' => 'decimal:2',
            'descuento_unitario' => 'decimal:2',
            'precio_unitario' => 'decimal:2',
            'costo_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }
}