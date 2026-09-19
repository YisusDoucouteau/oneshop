<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class UnidadAdquirida extends Model
{
    protected $table = 'unidades_adquiridas';

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public const ESTADO_PENDIENTE_LLEGADA =
        'PENDIENTE_LLEGADA';

    public const ESTADO_RECIBIDA_ORIGEN =
        'RECIBIDA_ORIGEN';

    public const ESTADO_EN_REVISION =
        'EN_REVISION';

    public const ESTADO_EN_PREPARACION =
        'EN_PREPARACION';

    public const ESTADO_LISTA_ENVIO =
        'LISTA_ENVIO';

    public const ESTADO_ENVIADA =
        'ENVIADA';

    public const ESTADO_RECIBIDA_ORURO =
        'RECIBIDA_ORURO';

    public const ESTADO_INCORPORADA =
        'INCORPORADA';

    public const ESTADO_ANULADA =
        'ANULADA';


    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'detalle_lote_id',
        'adquisicion_directa_id',
        'producto_id',

        'nombre_equipo',
        'modelo_equipo',

        'precio_compra',
        'moneda_id',
        'tipo_cambio_compra_id',
        'precio_compra_bob',

        'fecha_compra',
        'referencia_compra',
        'proveedor_compra',

        'almacen_actual_id',
        'estado',
        'codigo_trazabilidad',

        'fecha_llegada',
        'fecha_revision',
        'fecha_lista_envio',

        'serial_fabricante',

        /*
         * Clasificación física/técnica.
         */
        'grado_recibido',
        'grado_final',

        'procesador',
        'generacion_procesador',

        'ram_gb',

        'almacenamiento_gb',
        'tipo_almacenamiento',

        'tarjeta_grafica',

        'pantalla_pulgadas',
        'resolucion',

        'sistema_operativo',
        'bateria_porcentaje',
        'checklist_tecnico',
        'resultado_revision',

        'enciende',
        'tiene_sistema_operativo',
        'tiene_cargador',

        'requiere_servicio',
        'servicio_requerido',
        'observacion_revision',

        'registrado_por_id',
        'revisado_por_id',

        'equipo_id',

        'motivo_anulacion',
        'anulado_por_id',
        'fecha_anulacion',
    ];


    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'fecha_llegada' =>
            'datetime',

        'fecha_revision' =>
            'datetime',

        'fecha_lista_envio' =>
            'datetime',

        'fecha_compra' =>
            'date',

        'fecha_anulacion' =>
            'datetime',

        'ram_gb' =>
            'integer',

        'almacenamiento_gb' =>
            'integer',

        'pantalla_pulgadas' =>
            'decimal:1',

        'bateria_porcentaje' =>
            'integer',

        'checklist_tecnico' =>
            'array',

        'enciende' =>
            'boolean',

        'tiene_sistema_operativo' =>
            'boolean',

        'tiene_cargador' =>
            'boolean',

        'requiere_servicio' =>
            'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relaciones de origen
    |--------------------------------------------------------------------------
    */

    public function detalleLote(): BelongsTo
    {
        return $this->belongsTo(
            DetalleLote::class,
            'detalle_lote_id'
        );
    }


    public function adquisicionDirecta(): BelongsTo
    {
        return $this->belongsTo(
            AdquisicionDirecta::class,
            'adquisicion_directa_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Producto y ubicación
    |--------------------------------------------------------------------------
    */

    public function producto(): BelongsTo
    {
        return $this->belongsTo(
            Producto::class,
            'producto_id'
        );
    }


    public function almacenActual(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_actual_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Usuarios
    |--------------------------------------------------------------------------
    */

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }


    public function revisadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revisado_por_id'
        );
    }


    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'anulado_por_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Inventario oficial
    |--------------------------------------------------------------------------
    */

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(
            Equipo::class,
            'equipo_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Intervenciones de preparación
    |--------------------------------------------------------------------------
    */

    public function intervenciones(): HasMany
    {
        return $this->hasMany(
            IntervencionUnidadAdquirida::class,
            'unidad_adquirida_id'
        )
            ->orderBy('fecha_inicio');
    }


    public function revisionesTecnicas(): HasMany
    {
        return $this->hasMany(
            RevisionTecnicaUnidadAdquirida::class,
            'unidad_adquirida_id'
        )
            ->orderBy('fecha_revision');
    }



    /*
    |--------------------------------------------------------------------------
    | Envío Cochabamba -> Oruro
    |--------------------------------------------------------------------------
    */

    public function envioImportacionUnidad(): HasOne
    {
        return $this->hasOne(
            EnvioImportacionUnidad::class,
            'unidad_adquirida_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Costos de importación del lote
    |--------------------------------------------------------------------------
    */

    public function asignacionesCostos(): HasMany
    {
        return $this->hasMany(
            AsignacionCostoUnidadAdquirida::class,
            'unidad_adquirida_id'
        );
    }


    public function historialCostos(): HasMany
    {
        return $this->hasMany(
            HistorialCostoUnidad::class,
            'unidad_adquirida_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Moneda / tipo de cambio de compra
    |--------------------------------------------------------------------------
    */

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


    /*
     * Se mantiene por compatibilidad con código
     * existente que utiliza $unidad->tipoCambio.
     */
    public function tipoCambio(): BelongsTo
    {
        return $this->belongsTo(
            TipoCambio::class,
            'tipo_cambio_compra_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers de procedencia
    |--------------------------------------------------------------------------
    */

    public function provieneDeLote(): bool
    {
        return $this->detalle_lote_id !== null;
    }


    public function provieneDeAdquisicionDirecta(): bool
    {
        return $this->adquisicion_directa_id !== null;
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers de inventario
    |--------------------------------------------------------------------------
    */

    public function estaIncorporadaInventario(): bool
    {
        return $this->equipo_id !== null;
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers de estado
    |--------------------------------------------------------------------------
    */

    public function estaEnRevision(): bool
    {
        return $this->estado ===
            self::ESTADO_EN_REVISION;
    }


    public function estaEnPreparacion(): bool
    {
        return $this->estado ===
            self::ESTADO_EN_PREPARACION;
    }


    public function estaListaParaEnvio(): bool
    {
        return $this->estado ===
            self::ESTADO_LISTA_ENVIO;
    }


    /*
    |--------------------------------------------------------------------------
    | Accesores
    |--------------------------------------------------------------------------
    */

    public function getAlmacenamientoCompletoAttribute(): ?string
    {
        if (!$this->almacenamiento_gb) {
            return null;
        }

        return
            $this->almacenamiento_gb
            . ' GB '
            . strtoupper(
                $this->tipo_almacenamiento
                ?? ''
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Costos de importación
    |--------------------------------------------------------------------------
    */

    public function totalCostosImportacion(): float
    {
        return (float) $this
            ->asignacionesCostos()
            ->sum(
                'monto_asignado_bob'
            );
    }


    public function getCostoImportacionBobAttribute(): float
    {
        return
            $this->calcularCostoImportacionActivo();
    }


    private function calcularCostoImportacionActivo(): float
    {
        return (float) $this
            ->asignacionesCostos()
            ->whereHas(
                'costoLote',
                function ($query) {
                    $query->where(
                        'estado',
                        'ACTIVO'
                    );
                }
            )
            ->get()
            ->sum(
                function ($asignacion) {
                    return
                        (float)
                        $asignacion
                            ->monto_asignado_bob
                        +
                        (float)
                        $asignacion
                            ->ajuste_redondeo_bob;
                }
            );
    }
    public function costosPreparacion(): HasMany
{
    return $this->hasMany(
        CostoUnidadAdquirida::class,
        'unidad_adquirida_id'
    )
    ->orderBy('fecha_costo');
}
    public function incorporacionInventario(): HasOne
{
    return $this->hasOne(
        IncorporacionUnidadAdquirida::class,
        'unidad_adquirida_id'
    );
}

}