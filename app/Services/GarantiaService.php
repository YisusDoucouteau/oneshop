<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleVenta;
use App\Models\Garantia;
use App\Models\PoliticaGarantia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GarantiaService
{
    /**
     * Crea una garantía desde un detalle de venta.
     *
     * La política se determina:
     * 1. Política específica del producto.
     * 2. Política por categoría del producto.
     *
     * Se guarda snapshot histórico de las condiciones.
     */
    public function crearDesdeDetalleVenta(
        DetalleVenta $detalleVenta
    ): Garantia {

        return DB::transaction(function () use ($detalleVenta) {


            $detalleVenta->load([
                'producto.categoria'
            ]);


            $politica = $this->obtenerPoliticaAplicable(
                $detalleVenta
            );


            if (!$politica) {

                throw new ReglaNegocioException(
                    'El producto no posee una política de garantía vigente.'
                );

            }


            $fechaInicio = now();

            $fechaFin = now()
                ->addMonths(
                    $politica->duracion_meses
                );


            return Garantia::create([

                'numero' =>
                    $this->generarNumero(),


                'detalle_venta_id' =>
                    $detalleVenta->id,


                'politica_garantia_id' =>
                    $politica->id,


                'fecha_inicio' =>
                    $fechaInicio,


                'fecha_fin' =>
                    $fechaFin,


                'fecha_limite_cambio_inicial' =>
                    $fechaInicio
                        ->copy()
                        ->addDays(7),


                'duracion_meses_snapshot' =>
                    $politica->duracion_meses,


                'condiciones_snapshot' =>
                    $politica->condiciones,


                'exclusiones_snapshot' =>
                    $politica->exclusiones,


                'estado' =>
                    'VIGENTE',

            ]);

        });
    }


    /**
     * Obtiene la política de garantía aplicable.
     */
    private function obtenerPoliticaAplicable(
        DetalleVenta $detalleVenta
    ): ?PoliticaGarantia {


        $hoy = now()->toDateString();



        // Primero busca una política específica del producto

        $politicaProducto = PoliticaGarantia::query()
            ->where(
                'producto_id',
                $detalleVenta->producto_id
            )
            ->where('activo', true)
            ->whereDate(
                'vigente_desde',
                '<=',
                $hoy
            )
            ->where(function ($query) use ($hoy) {

                $query->whereNull('vigente_hasta')
                    ->orWhereDate(
                        'vigente_hasta',
                        '>=',
                        $hoy
                    );

            })
            ->first();


        if ($politicaProducto) {

            return $politicaProducto;

        }




        // Si no existe, busca por categoría

        return PoliticaGarantia::query()
            ->where(
                'categoria_producto_id',
                $detalleVenta
                    ->producto
                    ->categoria
                    ->id
            )
            ->where('activo', true)
            ->whereDate(
                'vigente_desde',
                '<=',
                $hoy
            )
            ->where(function ($query) use ($hoy) {

                $query->whereNull('vigente_hasta')
                    ->orWhereDate(
                        'vigente_hasta',
                        '>=',
                        $hoy
                    );

            })
            ->first();

    }



    /**
     * Genera número único de garantía.
     */
    private function generarNumero(): string
    {

        return 'GRT-'
            . now()->format('Ymd')
            . '-'
            . Str::upper(
                Str::ulid()
            );

    }
}