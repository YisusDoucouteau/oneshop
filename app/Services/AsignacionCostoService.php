<?php

namespace App\Services;

use App\Models\CostoLote;
use App\Models\UnidadAdquirida;
use App\Models\AsignacionCostoUnidadAdquirida;
use Illuminate\Support\Facades\DB;
use Exception;

class AsignacionCostoService
{

    public function distribuirPorUnidad(
        CostoLote $costo
    ): bool {


        return DB::transaction(function () use ($costo) {



            /*
            |--------------------------------------------------------------------------
            | VALIDAR COSTO
            |--------------------------------------------------------------------------
            */


            if ($costo->estado !== 'ACTIVO') {

                throw new Exception(
                    'No se puede distribuir un costo anulado.'
                );

            }





            /*
            |--------------------------------------------------------------------------
            | OBTENER EQUIPOS RECIBIDOS DEL LOTE
            |--------------------------------------------------------------------------
            */


            $unidades = UnidadAdquirida::query()

                ->whereHas(
                    'detalleLote',
                    function ($query) use ($costo) {

                        $query->where(
                            'lote_id',
                            $costo->lote_id
                        );

                    }
                )

                ->where(
                    'estado',
                    '!=',
                    UnidadAdquirida::ESTADO_ANULADA
                )

                ->orderBy('id')

                ->get();





            $totalUnidades =
                $unidades->count();




            if ($totalUnidades === 0) {

                throw new Exception(
                    'No existen equipos recibidos activos para distribuir este costo.'
                );

            }







            /*
            |--------------------------------------------------------------------------
            | LIMPIAR DISTRIBUCIÓN ANTERIOR
            |--------------------------------------------------------------------------
            |
            | Permite:
            | - editar costos
            | - cambiar cantidades
            | - redistribuir correctamente
            |
            */


            AsignacionCostoUnidadAdquirida::where(
                'costo_lote_id',
                $costo->id
            )->delete();







            /*
            |--------------------------------------------------------------------------
            | DISTRIBUCIÓN EXACTA POR CENTAVOS
            |--------------------------------------------------------------------------
            */


            $montoTotalCentavos =
                (int) round(
                    ((float)$costo->monto_bob) * 100
                );



            $montoBaseCentavos =
                intdiv(
                    $montoTotalCentavos,
                    $totalUnidades
                );



            $centavosRestantes =
                $montoTotalCentavos
                %
                $totalUnidades;








            /*
            |--------------------------------------------------------------------------
            | CREAR ASIGNACIONES
            |--------------------------------------------------------------------------
            */


            foreach(
                $unidades as $indice => $unidad
            ) {



                $montoBase =
                    $montoBaseCentavos / 100;



                $ajusteRedondeo =
                    $indice < $centavosRestantes
                    ? 0.01
                    : 0.00;





                AsignacionCostoUnidadAdquirida::create([


                    'costo_lote_id' =>
                        $costo->id,


                    'unidad_adquirida_id' =>
                        $unidad->id,



                    'metodo_asignacion' =>
                        'PRORRATEO',



                    'base_individual' =>
                        1,



                    'base_total' =>
                        $totalUnidades,



                    'porcentaje' =>
                        round(
                            100 / $totalUnidades,
                            6
                        ),



                    'monto_asignado_bob' =>
                        $montoBase,



                    'ajuste_redondeo_bob' =>
                        $ajusteRedondeo,



                    'observacion' =>
                        'Distribución automática por cantidad de unidades',


                ]);


            }





            return true;



        });



    }







    /*
    |--------------------------------------------------------------------------
    | REDISTRIBUIR COSTO MANUALMENTE
    |--------------------------------------------------------------------------
    */


    public function redistribuirCosto(
        CostoLote $costo
    ): bool {


        return $this->distribuirPorUnidad(
            $costo
        );


    }


}