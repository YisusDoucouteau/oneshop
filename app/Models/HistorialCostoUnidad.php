<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialCostoUnidad extends Model
{
    protected $table =
        'historial_costos_unidades';


    protected $fillable = [

        'unidad_adquirida_id',

        'costo_compra',

        'costos_lote',

        'intervenciones',

        'costo_total',

        'completo',

        'detalle_json',

        'calculado_por_id',

        'fecha_calculo',

    ];



    protected $casts = [

        'costo_compra' =>
            'decimal:2',

        'costos_lote' =>
            'decimal:2',

        'intervenciones' =>
            'decimal:2',

        'costo_total' =>
            'decimal:2',


        'completo' =>
            'boolean',


        'detalle_json' =>
            'array',


        'fecha_calculo' =>
            'datetime',

    ];



    public function unidadAdquirida(): BelongsTo
    {
        return $this->belongsTo(
            UnidadAdquirida::class,
            'unidad_adquirida_id'
        );
    }



    public function calculadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'calculado_por_id'
        );
    }

}