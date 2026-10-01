<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegularizacionValoracionInventario extends Model
{
    protected $table = 'regularizaciones_valoracion_inventario';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'usuario_id',
        'cantidad_disponible_snapshot',
        'cantidad_reservada_snapshot',
        'stock_fisico_snapshot',
        'costo_promedio_anterior_bob',
        'costo_promedio_resultante_bob',
        'valor_total_bob',
        'referencia',
        'motivo',
        'fecha_regularizacion',
    ];

    protected $casts = [
        'cantidad_disponible_snapshot' => 'integer',
        'cantidad_reservada_snapshot' => 'integer',
        'stock_fisico_snapshot' => 'integer',
        'costo_promedio_anterior_bob' => 'decimal:6',
        'costo_promedio_resultante_bob' => 'decimal:6',
        'valor_total_bob' => 'decimal:2',
        'fecha_regularizacion' => 'datetime',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
