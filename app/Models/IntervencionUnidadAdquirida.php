<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntervencionUnidadAdquirida extends Model
{
    protected $table =
        'intervenciones_unidades_adquiridas';

    public const TIPO_COMPONENTE =
        'COMPONENTE';

    public const TIPO_SERVICIO =
        'SERVICIO';

    public const ORIGEN_COMPRA_EXTERNA =
        'COMPRA_EXTERNA';

    public const ORIGEN_STOCK =
        'STOCK';

    protected $fillable = [
        'unidad_adquirida_id',
        'tipo',

        'producto_id',
        'origen_componente',
        'cantidad',
        'almacen_id',
        'movimiento_inventario_id',

        'tipo_costo_id',
        'moneda_id',
        'tipo_cambio_id',
        'monto_origen',
        'monto_bob',

        'fecha_inicio',
        'fecha_fin',

        'descripcion',
        'resultado',
        'referencia',
        'registrado_por_id',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'integer',

        'monto_origen' => 'decimal:2',
        'monto_bob' => 'decimal:2',

        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function unidadAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            UnidadAdquirida::class,
            'unidad_adquirida_id'
        );
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_id'
        );
    }

    public function movimientoInventario(): BelongsTo
    {
        return $this->belongsTo(
            MovimientoInventario::class,
            'movimiento_inventario_id'
        );
    }

    public function tipoCosto(): BelongsTo
    {
        return $this->belongsTo(
            TipoCosto::class,
            'tipo_costo_id'
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

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }

    public function esComponente(): bool
    {
        return $this->tipo === self::TIPO_COMPONENTE;
    }

    public function esServicio(): bool
    {
        return $this->tipo === self::TIPO_SERVICIO;
    }

    public function provieneDeStock(): bool
    {
        return $this->origen_componente
            === self::ORIGEN_STOCK;
    }

    public function esCompraExterna(): bool
    {
        return $this->origen_componente
            === self::ORIGEN_COMPRA_EXTERNA;
    }
}