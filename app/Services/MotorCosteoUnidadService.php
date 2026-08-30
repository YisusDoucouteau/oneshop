<?php

namespace App\Services;

use App\Models\UnidadAdquirida;

class MotorCosteoUnidadService
{
    public function calcularCostoUnidad(
        UnidadAdquirida $unidad
    ): array {

        $unidad->load([
            'detalleLote',
            'asignacionesCostos',
            'intervenciones',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Costo base de adquisición
        |--------------------------------------------------------------------------
        |
        | Es el costo unitario de compra registrado
        | en el detalle del lote.
        |
        */

        $costoCompra =
            (float) (
                $unidad
                    ->detalleLote
                    ->costo_unitario_bob
                ?? 0
            );



        /*
        |--------------------------------------------------------------------------
        | Costos generales asignados
        |--------------------------------------------------------------------------
        */

        $costosLote =
            $unidad
                ->asignacionesCostos
                ->sum(
                    'monto_asignado_bob'
                );



        /*
        |--------------------------------------------------------------------------
        | Costos particulares de la unidad
        |--------------------------------------------------------------------------
        |
        | Ejemplo:
        | SSD comprado
        | cargador externo
        | servicio técnico
        |
        */

       $intervenciones =
    $unidad
        ->intervenciones
        ->whereNotNull(
            'monto_bob'
        )
        ->sum(
            'monto_bob'
        );


$intervencionesSinCosto =
    $unidad
        ->intervenciones
        ->whereNull(
            'monto_bob'
        )
        ->count();



$advertencias = [];

$completo = true;


if ($intervencionesSinCosto > 0) {

    $completo = false;

    $advertencias[] =
        'Existe una intervención sin costo registrado.';
}



        $costoTotal =
            $costoCompra
            +
            $costosLote
            +
            $intervenciones;



        return [

            'unidad_id' =>
                $unidad->id,


            'codigo_trazabilidad' =>
                $unidad->codigo_trazabilidad,


            'costo_compra' =>
                round(
                    $costoCompra,
                    2
                ),


            'costos_lote' =>
                round(
                    $costosLote,
                    2
                ),


            'intervenciones' =>
                round(
                    $intervenciones,
                    2
                ),


            'costo_total' =>
                round(
                    $costoTotal,
                    2
                ),


            'completo' =>
    $completo,
'advertencias' =>
    $advertencias,
        ];
    }
}