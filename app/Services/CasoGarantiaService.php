<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\CasoGarantia;
use App\Models\Garantia;
use App\Models\IntervencionGarantia;
use App\Models\CambioEquipo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CasoGarantiaService
{

    public function abrirCaso(
        int $garantiaId,
        int $usuarioId,
        string $motivoCliente,
        ?string $observacion = null
    ): CasoGarantia {

        return DB::transaction(function () use (
            $garantiaId,
            $usuarioId,
            $motivoCliente,
            $observacion
        ) {


            $garantia = Garantia::with(
                'detalleVenta'
            )->findOrFail($garantiaId);



            if (!$garantia->estaVigente()) {

                throw new ReglaNegocioException(
                    'La garantía no se encuentra vigente.'
                );

            }



            $casoAbierto = CasoGarantia::where(
                    'garantia_id',
                    $garantia->id
                )
                ->whereIn(
                    'estado',
                    [
                        'ABIERTO',
                        'DIAGNOSTICADO',
                        'EN_PROCESO'
                    ]
                )
                ->exists();



            if ($casoAbierto) {

                throw new ReglaNegocioException(
                    'Ya existe un caso abierto para esta garantía.'
                );

            }



            return CasoGarantia::create([

                'numero' =>
                    'CASO-' . strtoupper(Str::random(8)),

                'garantia_id' =>
                    $garantia->id,

                'equipo_afectado_id' =>
                    $garantia->detalleVenta->equipo_id,

                'recibido_por_id' =>
                    $usuarioId,

                'tipo_caso' =>
                    'GARANTIA',

                'estado' =>
                    'ABIERTO',

                'fecha_apertura' =>
                    now(),

                'motivo_cliente' =>
                    $motivoCliente,

                'observacion' =>
                    $observacion,

            ]);

        });

    }




    public function registrarDiagnostico(
        int $casoId,
        string $diagnostico
    ): CasoGarantia {


        $caso = CasoGarantia::findOrFail($casoId);



        $caso->update([

            'diagnostico_final' =>
                $diagnostico,

            'estado' =>
                'DIAGNOSTICADO',

        ]);



        return $caso->fresh();

    }




    public function registrarIntervencion(
        int $casoId,
        int $usuarioId,
        string $tipo,
        string $descripcion,
        ?string $resultado = null
    ): IntervencionGarantia {


        return IntervencionGarantia::create([

            'caso_garantia_id' =>
                $casoId,

            'usuario_id' =>
                $usuarioId,

            'tipo_intervencion' =>
                $tipo,

            'fecha_intervencion' =>
                now(),

            'descripcion' =>
                $descripcion,

            'resultado' =>
                $resultado,

        ]);

    }




    public function cerrarCaso(
        int $casoId,
        int $usuarioId,
        string $resolucion
    ): CasoGarantia {


        $caso = CasoGarantia::findOrFail($casoId);



        $caso->update([

            'estado' =>
                'CERRADO',

            'resolucion' =>
                $resolucion,

            'fecha_cierre' =>
                now(),

            'cerrado_por_id' =>
                $usuarioId,

        ]);



        return $caso->fresh();

    }




    public function registrarCambioEquipo(
        int $casoId,
        int $equipoSalienteId,
        int $equipoEntranteId,
        int $usuarioId,
        string $motivo
    ): CambioEquipo {


        if ($equipoSalienteId === $equipoEntranteId) {

            throw new ReglaNegocioException(
                'El equipo entrante no puede ser igual al equipo saliente.'
            );

        }



        return CambioEquipo::create([

            'caso_garantia_id' =>
                $casoId,

            'equipo_saliente_id' =>
                $equipoSalienteId,

            'equipo_entrante_id' =>
                $equipoEntranteId,

            'autorizado_por_id' =>
                $usuarioId,

            'fecha_cambio' =>
                now(),

            'motivo' =>
                $motivo,

        ]);

    }

}