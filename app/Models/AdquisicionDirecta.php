<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdquisicionDirecta extends Model
{
    protected $table = 'adquisiciones_directas';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'almacen_recepcion_id',
        'cantidad_esperada',
        'moneda_id',
        'tipo_cambio_compra_id',
        'costo_unitario_origen',
        'costo_unitario_bob',
        'fecha_adquisicion',
        'comprado_por_id',
        'referencia_compra',
        'origen',
        'registrado_por_id',
        'observacion',
    ];

    protected $casts = [
        'cantidad_esperada' => 'integer',
        'costo_unitario_origen' => 'decimal:2',
        'costo_unitario_bob' => 'decimal:2',
        'fecha_adquisicion' => 'date',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(
            Proveedor::class,
            'proveedor_id'
        );
    }

    public function almacenRecepcion(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_recepcion_id'
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

    public function compradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'comprado_por_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }

    public function unidadesAdquiridas(): HasMany
    {
        return $this->hasMany(
            UnidadAdquirida::class,
            'adquisicion_directa_id'
        );
    }
}