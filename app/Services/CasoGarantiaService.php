<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\CasoGarantia;
use App\Models\CambioEquipo;
use App\Models\Garantia;
use App\Models\IntervencionGarantia;
use App\Models\User;
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
            $usuario = User::query()
                ->where('activo', true)
                ->find($usuarioId);

            if (!$usuario) {
                throw new ReglaNegocioException(
                    'El usuario no existe o se encuentra inactivo.'
                );
            }

            if (!$usuario->tienePermiso('garantias.registrar')) {
                throw new ReglaNegocioException(
                    'El usuario no cuenta con permiso para registrar casos de garantía.'
                );
            }

            $motivoCliente = trim($motivoCliente);

            if ($motivoCliente === '') {
                throw new ReglaNegocioException(
                    'Debe indicar el motivo reportado por el cliente.'
                );
            }

            $observacion = $observacion !== null
                ? trim($observacion)
                : null;

            $garantia = Garantia::query()
                ->with([
                    'detalleVenta.venta',
                    'detalleVenta.equipo',
                ])
                ->lockForUpdate()
                ->find($garantiaId);

            if (!$garantia) {
                throw new ReglaNegocioException(
                    'La garantía no existe.'
                );
            }

            if (!$garantia->estaVigente()) {
                throw new ReglaNegocioException(
                    'La garantía no se encuentra vigente.'
                );
            }

            $detalleVenta = $garantia->detalleVenta;

            if (!$detalleVenta) {
                throw new ReglaNegocioException(
                    'La garantía no posee un detalle de venta asociado.'
                );
            }

            $venta = $detalleVenta->venta;

            if (
                !$venta
                || $venta->estado === 'ANULADA'
            ) {
                throw new ReglaNegocioException(
                    'No se puede abrir un caso sobre una venta anulada o inexistente.'
                );
            }

            $equipo = $detalleVenta->equipo;

            if (!$equipo) {
                throw new ReglaNegocioException(
                    'La garantía no posee un equipo asociado.'
                );
            }

            if (!$equipo->activo) {
                throw new ReglaNegocioException(
                    'El equipo asociado se encuentra inactivo.'
                );
            }

            $casoAbierto = CasoGarantia::query()
                ->where(
                    'garantia_id',
                    $garantia->id
                )
                ->whereIn(
                    'estado',
                    [
                        'ABIERTO',
                        'DIAGNOSTICADO',
                        'EN_PROCESO',
                    ]
                )
                ->lockForUpdate()
                ->first();

            if ($casoAbierto) {
                throw new ReglaNegocioException(
                    'Ya existe un caso abierto para esta garantía.'
                );
            }

            return CasoGarantia::query()->create([
                'numero' =>
                    'CAS-GAR-'
                    . now()->format('Ymd')
                    . '-'
                    . Str::upper(Str::ulid()),

                'garantia_id' =>
                    $garantia->id,

                'equipo_afectado_id' =>
                    $equipo->id,

                'recibido_por_id' =>
                    $usuario->id,

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
        }, 3);
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
