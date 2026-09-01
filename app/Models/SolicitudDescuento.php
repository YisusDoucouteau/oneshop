<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudDescuento extends Model
{
    protected $table = 'solicitudes_descuentos';


    protected $fillable = [

        'precio_equipo_id',

        'politica_descuento_id',

        'cliente_id',

        'solicitado_por_id',

        'precio_publico_snapshot',

        'precio_solicitado',

        'descuento_solicitado',

        'porcentaje_descuento',

        'costo_total_snapshot',

        'utilidad_proyectada',

        'estado',

        'motivo',

        'respondido_por_id',

        'fecha_respuesta',

        'motivo_respuesta',
    ];



    protected $casts = [

        'precio_publico_snapshot' =>
            'decimal:2',

        'precio_solicitado' =>
            'decimal:2',

        'descuento_solicitado' =>
            'decimal:2',

        'porcentaje_descuento' =>
            'decimal:4',

        'costo_total_snapshot' =>
            'decimal:2',

        'utilidad_proyectada' =>
            'decimal:2',

        'fecha_respuesta' =>
            'datetime',
    ];



    public function precioEquipo(): BelongsTo
    {
        return $this->belongsTo(
            PrecioEquipo::class,
            'precio_equipo_id'
        );
    }



    public function politicaDescuento(): BelongsTo
    {
        return $this->belongsTo(
            PoliticaDescuento::class,
            'politica_descuento_id'
        );
    }



    public function cliente(): BelongsTo
    {
        return $this->belongsTo(
            Cliente::class
        );
    }



    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'solicitado_por_id'
        );
    }



    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'respondido_por_id'
        );
    }
}