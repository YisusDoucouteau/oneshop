<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnvioImportacion extends Model
{
    protected $table = 'envios_importacion';

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public const ESTADO_BORRADOR =
    'BORRADOR';

public const ESTADO_PREPARADO =
    'PREPARADO';

public const ESTADO_DESPACHADO =
    'DESPACHADO';

public const ESTADO_RECIBIDO_PARCIAL =
    'RECIBIDO_PARCIAL';

public const ESTADO_RECIBIDO =
    'RECIBIDO';

public const ESTADO_CANCELADO =
    'CANCELADO';
public function estaPreparado(): bool
{
    return $this->estado ===
        self::ESTADO_PREPARADO;
}
    protected $fillable = [
        'codigo',

        'almacen_origen_id',
        'almacen_destino_id',

        'estado',

        'preparado_por_id',
        'despachado_por_id',
        'recibido_por_id',
        'verificado_recepcion_por_id',

        'fecha_preparacion',
        'fecha_despacho',
        'fecha_recepcion',
        'fecha_verificacion_recepcion',

        'transportista',
        'numero_guia',
        'cantidad_bultos',
        'cantidad_bultos_recibidos',
        'cantidad_cargadores',
        'cantidad_cargadores_adicionales_recibidos',
        'cantidad_accesorios',
        'cantidad_accesorios_recibidos',
        'detalle_accesorios',
        'observacion_recepcion_general',

        'observacion',
    ];

    protected $casts = [
        'fecha_preparacion' =>
            'datetime',

        'fecha_despacho' =>
            'datetime',

        'fecha_recepcion' =>
            'datetime',

        'fecha_verificacion_recepcion' =>
            'datetime',

        'cantidad_bultos' =>
            'integer',

        'cantidad_bultos_recibidos' =>
            'integer',

        'cantidad_cargadores' =>
            'integer',

        'cantidad_cargadores_adicionales_recibidos' =>
            'integer',

        'cantidad_accesorios' =>
            'integer',

        'cantidad_accesorios_recibidos' =>
            'integer',
    ];


    /*
    |--------------------------------------------------------------------------
    | Almacenes
    |--------------------------------------------------------------------------
    */

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_origen_id'
        );
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_destino_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Responsables
    |--------------------------------------------------------------------------
    */

    public function preparadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'preparado_por_id'
        );
    }

    public function despachadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'despachado_por_id'
        );
    }

    public function recibidoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recibido_por_id'
        );
    }

    public function verificadoRecepcionPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'verificado_recepcion_por_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Unidades incluidas
    |--------------------------------------------------------------------------
    */

    public function unidadesEnvio(): HasMany
    {
        return $this->hasMany(
            EnvioImportacionUnidad::class,
            'envio_importacion_id'
        );
    }



    /**
     * Cantidad de cargadores que viajan asociados directamente
     * a las unidades incluidas en este envío.
     */
    public function cantidadCargadoresAsociados(): int
    {
        if ($this->relationLoaded('unidadesEnvio')) {
            return $this->unidadesEnvio
                ->where('incluye_cargador', true)
                ->count();
        }

        return $this->unidadesEnvio()
            ->where('incluye_cargador', true)
            ->count();
    }

    /**
     * Total físico de cargadores declarados en el traslado:
     * cargadores asociados a equipos + cargadores adicionales/sueltos.
     */
    public function cantidadCargadoresTotales(): int
    {
        return $this->cantidadCargadoresAsociados()
            + (int) ($this->cantidad_cargadores ?? 0);
    }

    /**
     * Indica si el destino ya registró el conteo físico general
     * necesario para poder cerrar la recepción.
     */
    public function recepcionGeneralVerificada(): bool
    {
        return $this->fecha_verificacion_recepcion !== null
            && $this->cantidad_bultos_recibidos !== null
            && $this->cantidad_cargadores_adicionales_recibidos !== null
            && $this->cantidad_accesorios_recibidos !== null;
    }

    /**
     * Compara el manifiesto de salida con el conteo físico en destino.
     *
     * Los cargadores asociados a equipos se controlan por unidad; aquí
     * se comparan únicamente cajas, cargadores adicionales y accesorios.
     */
    public function tieneDiferenciasConteoRecepcion(): bool
    {
        if (!$this->recepcionGeneralVerificada()) {
            return false;
        }

        return (int) $this->cantidad_bultos_recibidos !== (int) $this->cantidad_bultos
            || (int) $this->cantidad_cargadores_adicionales_recibidos !== (int) ($this->cantidad_cargadores ?? 0)
            || (int) $this->cantidad_accesorios_recibidos !== (int) ($this->cantidad_accesorios ?? 0);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(
            Auditoria::class,
            'entidad_id'
        )
            ->where('entidad', 'EnvioImportacion')
            ->orderBy('fecha_evento');
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers de estado
    |--------------------------------------------------------------------------
    */

    public function estaEnBorrador(): bool
    {
        return $this->estado ===
            self::ESTADO_BORRADOR;
    }

    public function estaDespachado(): bool
    {
        return $this->estado ===
            self::ESTADO_DESPACHADO;
    }

    public function estaCerrado(): bool
{
    return in_array(
        $this->estado,
        [
            self::ESTADO_RECIBIDO,
            self::ESTADO_CANCELADO,
        ],
        true
    );
}

    public function puedeModificarse(): bool
    {
        return $this->estado ===
            self::ESTADO_BORRADOR;
    }
}