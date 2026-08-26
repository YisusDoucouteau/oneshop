<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvioImportacionUnidad extends Model
{
    protected $table =
        'envios_importacion_unidades';

    /*
    |--------------------------------------------------------------------------
    | Estados de recepción
    |--------------------------------------------------------------------------
    */

    public const ESTADO_PENDIENTE =
        'PENDIENTE';

    public const ESTADO_RECIBIDA =
        'RECIBIDA';

    public const ESTADO_FALTANTE =
        'FALTANTE';

    public const ESTADO_INCIDENCIA =
        'INCIDENCIA';

    protected $fillable = [
        'envio_importacion_id',
        'unidad_adquirida_id',

        'estado_recepcion',

        'fecha_recepcion',
        'recibido_por_id',

        'observacion_recepcion',
    ];

    protected $casts = [
        'fecha_recepcion' =>
            'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function envioImportacion(): BelongsTo
    {
        return $this->belongsTo(
            EnvioImportacion::class,
            'envio_importacion_id'
        );
    }

    public function unidadAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            UnidadAdquirida::class,
            'unidad_adquirida_id'
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
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function estaPendiente(): bool
    {
        return $this->estado_recepcion ===
            self::ESTADO_PENDIENTE;
    }

    public function fueRecibida(): bool
    {
        return $this->estado_recepcion ===
            self::ESTADO_RECIBIDA;
    }

    public function estaFaltante(): bool
    {
        return $this->estado_recepcion ===
            self::ESTADO_FALTANTE;
    }

    public function tieneIncidencia(): bool
    {
        return $this->estado_recepcion ===
            self::ESTADO_INCIDENCIA;
    }

    public function estaResueltaEnRecepcion(): bool
    {
        return in_array(
            $this->estado_recepcion,
            [
                self::ESTADO_RECIBIDA,
                self::ESTADO_FALTANTE,
                self::ESTADO_INCIDENCIA,
            ],
            true
        );
    }
}