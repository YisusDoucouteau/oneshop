<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidenciaLogisticaImportacion extends Model
{
    protected $table =
        'incidencias_logisticas_importacion';


    /*
    |--------------------------------------------------------------------------
    | Estados de gestión
    |--------------------------------------------------------------------------
    */

    public const ESTADO_ABIERTA =
        'ABIERTA';

    public const ESTADO_EN_GESTION =
        'EN_GESTION';

    public const ESTADO_RESUELTA =
        'RESUELTA';


    /*
    |--------------------------------------------------------------------------
    | Asignación masiva
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'envio_importacion_unidad_id',

        'tipo',
        'estado',
        'descripcion',

        'fecha_apertura',
        'abierta_por_id',

        'resultado',
        'detalle_resolucion',
        'fecha_resolucion',
        'resuelta_por_id',
    ];


    /*
    |--------------------------------------------------------------------------
    | Conversiones
    |--------------------------------------------------------------------------
    */

    protected $casts = [
        'fecha_apertura' =>
            'datetime',

        'fecha_resolucion' =>
            'datetime',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function envioImportacionUnidad(): BelongsTo
    {
        return $this->belongsTo(
            EnvioImportacionUnidad::class,
            'envio_importacion_unidad_id'
        );
    }


    public function abiertaPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'abierta_por_id'
        );
    }


    public function resueltaPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'resuelta_por_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function estaAbierta(): bool
    {
        return $this->estado ===
            self::ESTADO_ABIERTA;
    }


    public function estaEnGestion(): bool
    {
        return $this->estado ===
            self::ESTADO_EN_GESTION;
    }


    public function estaResuelta(): bool
    {
        return $this->estado ===
            self::ESTADO_RESUELTA;
    }


    public function puedeGestionarse(): bool
    {
        return in_array(
            $this->estado,
            [
                self::ESTADO_ABIERTA,
                self::ESTADO_EN_GESTION,
            ],
            true
        );
    }
}