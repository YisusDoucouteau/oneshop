<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\PoliticaDescuento;
use App\Models\PrecioEquipo;
use App\Models\SolicitudDescuento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ValidadorVentaPrecioService
{
    public function validar(
        int $equipoId,
        float $precioPropuesto,
        ?int $clienteId = null,
        ?int $vendedorId = null
    ): array {

        if ($precioPropuesto <= 0) {
            throw new InvalidArgumentException(
                'El precio propuesto debe ser mayor a cero.'
            );
        }


        $equipo = Equipo::query()
            ->with('producto')
            ->find($equipoId);


        if (!$equipo) {
            throw new InvalidArgumentException(
                'El equipo no existe.'
            );
        }


        $precio = PrecioEquipo::query()
            ->where('equipo_id', $equipo->id)
            ->where('vigente', true)
            ->orderByDesc('vigente_desde')
            ->first();


        if (!$precio) {
            throw new InvalidArgumentException(
                'El equipo no posee precio vigente.'
            );
        }


        $precioPublicado =
            (float) $precio->precio_publico;


        $costo =
            (float) $precio->costo_total_snapshot;


        $descuento =
            $precioPublicado - $precioPropuesto;


        $porcentaje =
            $precioPublicado > 0
                ? ($descuento / $precioPublicado) * 100
                : 0;


        $utilidad =
            $precioPropuesto - $costo;



        $politica =
            PoliticaDescuento::query()
                ->where('activo', true)
                ->first();



        $cumplePolitica = true;

        $requiereAprobacion = false;



        if ($politica) {

            if (
                $porcentaje >
                (float) $politica->porcentaje_maximo
            ) {
                $cumplePolitica = false;
            }


            if (
                $utilidad <
                (float) $politica->utilidad_minima_bob
            ) {
                $cumplePolitica = false;
            }


            if (
                !$cumplePolitica &&
                $politica->requiere_autorizacion
            ) {
                $requiereAprobacion = true;
            }
        }



        $solicitud = null;


        if (
            !$cumplePolitica &&
            $vendedorId !== null
        ) {

            $solicitud =
                $this->crearSolicitud(
                    precio: $precio,
                    politica: $politica,
                    clienteId: $clienteId,
                    vendedorId: $vendedorId,
                    precioPropuesto: $precioPropuesto,
                    descuento: $descuento,
                    porcentaje: $porcentaje,
                    costo: $costo,
                    utilidad: $utilidad
                );
        }



        return [

            'permitido' =>
                $cumplePolitica,


            'precio_publicado' =>
                $precioPublicado,


            'precio_propuesto' =>
                $precioPropuesto,


            'descuento' =>
                round($descuento, 2),


            'porcentaje_descuento' =>
                round($porcentaje, 2),


            'utilidad' =>
                round($utilidad, 2),


            'politica' =>
                $politica,


            'requiere_aprobacion' =>
                $requiereAprobacion,


            'solicitud' =>
                $solicitud,


            'estado' =>
                $cumplePolitica
                    ? 'APROBADO'
                    : 'REQUIERE_REVISION',
        ];
    }



    private function crearSolicitud(
        PrecioEquipo $precio,
        ?PoliticaDescuento $politica,
        ?int $clienteId,
        int $vendedorId,
        float $precioPropuesto,
        float $descuento,
        float $porcentaje,
        float $costo,
        float $utilidad
    ): SolicitudDescuento {


        return DB::transaction(function () use (
            $precio,
            $politica,
            $clienteId,
            $vendedorId,
            $precioPropuesto,
            $descuento,
            $porcentaje,
            $costo,
            $utilidad
        ) {


            return SolicitudDescuento::create([

                'precio_equipo_id' =>
                    $precio->id,


                'politica_descuento_id' =>
                    $politica?->id,


                'cliente_id' =>
                    $clienteId,


                'solicitado_por_id' =>
                    $vendedorId,


                'precio_publico_snapshot' =>
                    $precio->precio_publico,


                'precio_solicitado' =>
                    $precioPropuesto,


                'descuento_solicitado' =>
                    $descuento,


                'porcentaje_descuento' =>
                    $porcentaje,


                'costo_total_snapshot' =>
                    $costo,


                'utilidad_proyectada' =>
                    $utilidad,


                'estado' =>
                    'PENDIENTE',


                'motivo' =>
                    'Descuento fuera de política comercial.',


                'respondido_por_id' =>
                    null,


                'fecha_respuesta' =>
                    null,


                'motivo_respuesta' =>
                    null,
            ]);
        });
    }
}