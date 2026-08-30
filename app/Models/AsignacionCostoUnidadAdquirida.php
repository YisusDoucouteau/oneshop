<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionCostoUnidadAdquirida extends Model
{
    protected $table =
        'asignaciones_costos_unidades_adquiridas';


    protected $fillable = [

        'costo_lote_id',

        'unidad_adquirida_id',

        'metodo_asignacion',

        'base_individual',

        'base_total',

        'porcentaje',

        'monto_asignado_bob',

        'ajuste_redondeo_bob',

        'observacion',

    ];


    protected $casts = [

        'base_individual' =>
            'decimal:4',

        'base_total' =>
            'decimal:4',

        'porcentaje' =>
            'decimal:6',

        'monto_asignado_bob' =>
            'decimal:2',

        'ajuste_redondeo_bob' =>
            'decimal:2',

    ];



    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */


    public function costoLote(): BelongsTo
    {
        return $this->belongsTo(
            CostoLote::class,
            'costo_lote_id'
        );
    }



    public function unidadAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            UnidadAdquirida::class,
            'unidad_adquirida_id'
        );
    }



    /*
    |--------------------------------------------------------------------------
    | Métodos auxiliares
    |--------------------------------------------------------------------------
    */


    public function esProrrateo(): bool
    {
        return $this->metodo_asignacion
            === 'PRORRATEO';
    }



    public function montoFinal(): float
    {
        return (float)
            $this->monto_asignado_bob
            +
            $this->ajuste_redondeo_bob;
    }
}