<?php

namespace App\Services;

use App\Models\HistorialCostoUnidad;
use App\Models\UnidadAdquirida;
use Illuminate\Support\Facades\DB;

class RegistroCostoUnidadService
{
    public function __construct(
        private MotorCosteoUnidadService $motorCosteo
    ) {
    }


    /**
     * Calcula y registra una nueva fotografía
     * del costo real de una unidad.
     */
    public function registrar(
        UnidadAdquirida $unidad,
        ?int $usuarioId = null
    ): HistorialCostoUnidad {

        return DB::transaction(
            function () use (
                $unidad,
                $usuarioId
            ) {

                $resultado =
                    $this->motorCosteo
                        ->calcularCostoUnidad(
                            $unidad
                        );


                return HistorialCostoUnidad::create([

                    'unidad_adquirida_id' =>
                        $unidad->id,

                    'costo_compra' =>
                        $resultado['costo_compra'],

                    'costos_lote' =>
                        $resultado['costos_lote'],

                    'intervenciones' =>
                        $resultado['intervenciones'],

                    'costo_total' =>
                        $resultado['costo_total'],

                    'completo' =>
                        $resultado['completo'],

                    'detalle_json' =>
                        $resultado,

                    'calculado_por_id' =>
                        $usuarioId,

                    'fecha_calculo' =>
                        now(),

                ]);
            }
        );
    }


    /**
     * Obtiene el último cálculo registrado
     * de una unidad.
     */
    public function ultimo(
        UnidadAdquirida $unidad
    ): ?HistorialCostoUnidad {

        return $unidad
            ->historialCostos()
            ->latest('fecha_calculo')
            ->first();
    }


    /**
     * Obtiene todo el historial de costos
     * de una unidad, del más reciente
     * al más antiguo.
     */
    public function historial(
        UnidadAdquirida $unidad
    ) {

        return $unidad
            ->historialCostos()
            ->orderByDesc('fecha_calculo')
            ->get();
    }
}