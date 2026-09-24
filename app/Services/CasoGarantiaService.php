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
        int $usuarioId,
        string $diagnostico
    ): CasoGarantia {
        return DB::transaction(function () use (
            $casoId,
            $usuarioId,
            $diagnostico
        ) {
            $usuario =
                $this->obtenerUsuarioGestorActivo(
                    $usuarioId
                );

            $diagnostico = trim($diagnostico);

            if ($diagnostico === '') {
                throw new ReglaNegocioException(
                    'Debe registrar el diagnóstico del caso.'
                );
            }

            $caso = CasoGarantia::query()
                ->lockForUpdate()
                ->find($casoId);

            if (!$caso) {
                throw new ReglaNegocioException(
                    'El caso de garantía no existe.'
                );
            }

            if ($caso->estado === 'CERRADO') {
                throw new ReglaNegocioException(
                    'El caso ya se encuentra cerrado y no puede modificarse.'
                );
            }

            if ($caso->estado !== 'ABIERTO') {
                throw new ReglaNegocioException(
                    'Solo los casos abiertos pueden recibir un diagnóstico inicial.'
                );
            }

            $caso->diagnostico_final =
                $diagnostico;

            $caso->estado =
                'DIAGNOSTICADO';

            $caso->save();

            return $caso->fresh([
                'garantia',
                'equipoAfectado',
                'recibidoPor',
                'cerradoPor',
                'intervenciones.usuario',
            ]);
        }, 3);
    }

    public function registrarIntervencion(
        int $casoId,
        int $usuarioId,
        string $tipo,
        string $descripcion,
        ?string $resultado = null
    ): IntervencionGarantia {
        return DB::transaction(function () use (
            $casoId,
            $usuarioId,
            $tipo,
            $descripcion,
            $resultado
        ) {
            $usuario =
                $this->obtenerUsuarioGestorActivo(
                    $usuarioId
                );

            $tipo = trim($tipo);
            $descripcion = trim($descripcion);
            $resultado = $resultado !== null
                ? trim($resultado)
                : null;

            if ($tipo === '') {
                throw new ReglaNegocioException(
                    'Debe indicar el tipo de intervención.'
                );
            }

            if ($descripcion === '') {
                throw new ReglaNegocioException(
                    'Debe describir la intervención realizada.'
                );
            }

            $caso = CasoGarantia::query()
                ->lockForUpdate()
                ->find($casoId);

            if (!$caso) {
                throw new ReglaNegocioException(
                    'El caso de garantía no existe.'
                );
            }

            if ($caso->estado === 'CERRADO') {
                throw new ReglaNegocioException(
                    'El caso ya se encuentra cerrado y no admite intervenciones.'
                );
            }

            if ($caso->estado === 'ABIERTO') {
                throw new ReglaNegocioException(
                    'Debe registrar el diagnóstico antes de agregar intervenciones.'
                );
            }

            if (
                !in_array(
                    $caso->estado,
                    [
                        'DIAGNOSTICADO',
                        'EN_PROCESO',
                    ],
                    true
                )
            ) {
                throw new ReglaNegocioException(
                    'El estado actual del caso no permite registrar intervenciones.'
                );
            }

            $intervencion =
                IntervencionGarantia::query()
                    ->create([
                        'caso_garantia_id' =>
                            $caso->id,

                        'usuario_id' =>
                            $usuario->id,

                        'tipo_intervencion' =>
                            $tipo,

                        'fecha_intervencion' =>
                            now(),

                        'descripcion' =>
                            $descripcion,

                        'resultado' =>
                            $resultado,
                    ]);

            if ($caso->estado === 'DIAGNOSTICADO') {
                $caso->estado =
                    'EN_PROCESO';

                $caso->save();
            }

            return $intervencion->fresh([
                'casoGarantia',
                'usuario',
            ]);
        }, 3);
    }

    public function cerrarCaso(
        int $casoId,
        int $usuarioId,
        string $resolucion
    ): CasoGarantia {
        return DB::transaction(function () use (
            $casoId,
            $usuarioId,
            $resolucion
        ) {
            $usuario =
                $this->obtenerUsuarioGestorActivo(
                    $usuarioId
                );

            $resolucion = trim($resolucion);

            if ($resolucion === '') {
                throw new ReglaNegocioException(
                    'Debe indicar la resolución final del caso.'
                );
            }

            $caso = CasoGarantia::query()
                ->lockForUpdate()
                ->find($casoId);

            if (!$caso) {
                throw new ReglaNegocioException(
                    'El caso de garantía no existe.'
                );
            }

            if ($caso->estado === 'CERRADO') {
                throw new ReglaNegocioException(
                    'El caso ya se encuentra cerrado.'
                );
            }

            if ($caso->estado === 'ABIERTO') {
                throw new ReglaNegocioException(
                    'Debe registrar un diagnóstico antes de cerrar el caso.'
                );
            }

            if (
                !in_array(
                    $caso->estado,
                    [
                        'DIAGNOSTICADO',
                        'EN_PROCESO',
                    ],
                    true
                )
            ) {
                throw new ReglaNegocioException(
                    'El estado actual del caso no permite cerrarlo.'
                );
            }

            if (
                trim(
                    (string) $caso->diagnostico_final
                ) === ''
            ) {
                throw new ReglaNegocioException(
                    'El caso no posee un diagnóstico registrado.'
                );
            }

            $caso->estado =
                'CERRADO';

            $caso->resolucion =
                $resolucion;

            $caso->fecha_cierre =
                now();

            $caso->cerrado_por_id =
                $usuario->id;

            $caso->save();

            return $caso->fresh([
                'garantia',
                'equipoAfectado',
                'recibidoPor',
                'cerradoPor',
                'intervenciones.usuario',
            ]);
        }, 3);
    }

    /*
     * Fase 10C.
     *
     * Se conserva el método existente, pero su endurecimiento
     * (disponibilidad del equipo entrante, inventario,
     * trazabilidad y autorización) se realizará en 10C.
     */
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

    private function obtenerUsuarioGestorActivo(
        int $usuarioId
    ): User {
        $usuario = User::query()
            ->where('activo', true)
            ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (
            !$usuario->tienePermiso(
                'garantias.gestionar'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para gestionar casos de garantía.'
            );
        }

        return $usuario;
    }
}
