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

        'fecha_preparacion',
        'fecha_despacho',
        'fecha_recepcion',

        'transportista',
        'numero_guia',
        'cantidad_bultos',
        'cantidad_cargadores',
        'cantidad_accesorios',
        'detalle_accesorios',

        'observacion',
    ];

    protected $casts = [
        'fecha_preparacion' =>
            'datetime',

        'fecha_despacho' =>
            'datetime',

        'fecha_recepcion' =>
            'datetime',

        'cantidad_bultos' =>
            'integer',

        'cantidad_cargadores' =>
            'integer',

        'cantidad_accesorios' =>
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