<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DetalleLote extends Model
{
    protected $table = 'detalles_lotes';

    protected $fillable = [
        'lote_id',
        'producto_id',
        'moneda_id',
        'tipo_cambio_compra_id',
        'cantidad_esperada',
        'cantidad_recibida',
        'costo_unitario_origen',
        'costo_unitario_bob',
        'observacion',
    ];

    protected $casts = [
            'cantidad_esperada' => 'integer',
            'cantidad_recibida' => 'integer',

            'costo_unitario_origen' => 'decimal:2',
            'costo_unitario_bob' => 'decimal:2',
        ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(
            Equipo::class,
            'detalle_lote_id'
        );
    }
    public function moneda(): BelongsTo
{
    return $this->belongsTo(
        Moneda::class,
        'moneda_id'
    );
}

public function tipoCambioCompra(): BelongsTo
{
    return $this->belongsTo(
        TipoCambio::class,
        'tipo_cambio_compra_id'
    );
}
}