<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\CambioEquipo;
use App\Models\CasoGarantia;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Garantia;
use App\Models\IntervencionGarantia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CasoGarantiaService
{
    public function __construct(
        private readonly MovimientoInventarioService $movimientoInventarioService,
        private readonly EstadoEquipoService $estadoEquipoService
    ) {
    }

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

    /**
     * Registra un cambio físico de equipo por garantía.
     *
     * Reglas:
     * - requiere permiso específico para autorizar cambios;
     * - el caso debe estar diagnosticado o en proceso;
     * - solo puede existir un cambio por caso;
     * - el equipo saliente debe ser el originalmente afectado;
     * - el equipo saliente debe continuar VENDIDO;
     * - el reemplazo debe estar DISPONIBLE;
     * - el reemplazo puede corresponder a otro producto, modelo o marca;
     * - el reemplazo debe estar activo, tener almacén y no poseer reserva activa;
     * - el reemplazo sale de su propio inventario disponible;
     * - el equipo original pasa a GARANTIA sin aumentar stock;
     * - el reemplazo pasa a VENDIDO;
     * - el caso queda EN_PROCESO y no se cierra automáticamente.
     */
    public function registrarCambioEquipo(
        int $casoId,
        int $equipoSalienteId,
        int $equipoEntranteId,
        int $usuarioId,
        string $motivo,
        ?string $observacion = null
    ): CambioEquipo {
        return DB::transaction(function () use (
            $casoId,
            $equipoSalienteId,
            $equipoEntranteId,
            $usuarioId,
            $motivo,
            $observacion
        ) {
            $usuario =
                $this->obtenerUsuarioAutorizadorCambioActivo(
                    $usuarioId
                );

            $motivo = trim($motivo);

            if ($motivo === '') {
                throw new ReglaNegocioException(
                    'Debe indicar el motivo del cambio de equipo.'
                );
            }

            if (mb_strlen($motivo) > 255) {
                throw new ReglaNegocioException(
                    'El motivo del cambio no puede superar los 255 caracteres.'
                );
            }

            $observacion = $observacion !== null
                ? trim($observacion)
                : null;

            if ($observacion === '') {
                $observacion = null;
            }

            if (
                $equipoSalienteId
                ===
                $equipoEntranteId
            ) {
                throw new ReglaNegocioException(
                    'El equipo entrante no puede ser igual al equipo saliente.'
                );
            }

            /*
             * Se bloquea primero el caso para serializar cualquier intento
             * concurrente de registrar un cambio sobre el mismo caso.
             */
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
                    'No se puede registrar un cambio de equipo en un caso cerrado.'
                );
            }

            if ($caso->estado === 'ABIERTO') {
                throw new ReglaNegocioException(
                    'El caso debe contar con un diagnóstico antes de autorizar un cambio de equipo.'
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
                    'El estado actual del caso no permite realizar un cambio de equipo.'
                );
            }

            /*
             * La tabla también posee UNIQUE(caso_garantia_id),
             * pero se valida primero como regla de negocio.
             */
            $cambioExistente = CambioEquipo::query()
                ->where(
                    'caso_garantia_id',
                    $caso->id
                )
                ->first();

            if ($cambioExistente) {
                throw new ReglaNegocioException(
                    'Este caso ya tiene un cambio de equipo registrado.'
                );
            }

            if (
                (int) $caso->equipo_afectado_id
                !==
                $equipoSalienteId
            ) {
                throw new ReglaNegocioException(
                    'El equipo saliente debe ser el equipo afectado originalmente por el caso.'
                );
            }

            /*
             * Los equipos se bloquean siempre en orden de ID.
             * Esto ayuda a mantener un orden consistente de bloqueos.
             */
            $equipos = Equipo::query()
                ->whereIn(
                    'id',
                    [
                        $equipoSalienteId,
                        $equipoEntranteId,
                    ]
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($equipos->count() !== 2) {
                throw new ReglaNegocioException(
                    'Uno o más equipos involucrados en el cambio no existen.'
                );
            }

            $equipoSaliente =
                $equipos->get($equipoSalienteId);

            $equipoEntrante =
                $equipos->get($equipoEntranteId);

            if (!$equipoSaliente->activo) {
                throw new ReglaNegocioException(
                    'El equipo saliente se encuentra inactivo.'
                );
            }

            if (!$equipoEntrante->activo) {
                throw new ReglaNegocioException(
                    'El equipo seleccionado como reemplazo se encuentra inactivo.'
                );
            }

            $estados = EstadoEquipo::query()
                ->whereIn(
                    'codigo',
                    [
                        'DISPONIBLE',
                        'VENDIDO',
                        'GARANTIA',
                    ]
                )
                ->where(
                    'activo',
                    true
                )
                ->get()
                ->keyBy('codigo');

            if (
                !$estados->has('DISPONIBLE')
                || !$estados->has('VENDIDO')
                || !$estados->has('GARANTIA')
            ) {
                throw new ReglaNegocioException(
                    'El catálogo de estados requerido para el cambio de garantía está incompleto.'
                );
            }

            if (
                (int) $equipoSaliente->estado_actual_id
                !==
                (int) $estados['VENDIDO']->id
            ) {
                throw new ReglaNegocioException(
                    'El equipo saliente debe continuar en estado VENDIDO antes de realizar el cambio.'
                );
            }

            if (
                (int) $equipoEntrante->estado_actual_id
                !==
                (int) $estados['DISPONIBLE']->id
            ) {
                throw new ReglaNegocioException(
                    'El equipo seleccionado como reemplazo no está disponible.'
                );
            }

            /*
             * El reemplazo puede pertenecer a otro producto, modelo o marca.
             * Su inventario se descontará utilizando el producto y almacén
             * propios del equipo seleccionado.
             */
            if ($equipoEntrante->almacen_actual_id === null) {
                throw new ReglaNegocioException(
                    'El equipo de reemplazo no tiene un almacén asignado.'
                );
            }

            /*
             * La interfaz oculta equipos reservados, pero esta regla también
             * se valida en el servicio para impedir forzar el cambio por POST.
             */
            if (
                $equipoEntrante
                    ->detallesReservas()
                    ->whereHas(
                        'reserva',
                        function ($query) {
                            $query->where(
                                'estado',
                                'ACTIVA'
                            );
                        }
                    )
                    ->exists()
            ) {
                throw new ReglaNegocioException(
                    'El equipo seleccionado como reemplazo posee una reserva activa.'
                );
            }

            /*
             * Primero se crea la trazabilidad del cambio.
             * Si algún paso posterior falla, la transacción completa
             * revertirá también este registro.
             */
            $cambio = CambioEquipo::query()->create([
                'caso_garantia_id' =>
                    $caso->id,

                'equipo_saliente_id' =>
                    $equipoSaliente->id,

                'equipo_entrante_id' =>
                    $equipoEntrante->id,

                'autorizado_por_id' =>
                    $usuario->id,

                'fecha_cambio' =>
                    now(),

                'motivo' =>
                    $motivo,

                'observacion' =>
                    $observacion,
            ]);

            /*
             * El equipo de reemplazo sale del stock disponible.
             * MovimientoInventarioService valida también existencia
             * y cantidad disponible.
             */
            $this->movimientoInventarioService
                ->registrarSalida(
                    productoId:
                        $equipoEntrante->producto_id,

                    almacenId:
                        $equipoEntrante->almacen_actual_id,

                    cantidad:
                        1,

                    tipoCodigo:
                        'CAMBIO_GARANTIA',

                    usuarioId:
                        $usuario->id,

                    tipoReferencia:
                        'CAMBIO_GARANTIA',

                    referenciaId:
                        $cambio->id,

                    observacion:
                        "Cambio por garantía del caso {$caso->numero}"
                );

            /*
             * El equipo original vuelve físicamente a OneShop,
             * pero NO retorna al inventario disponible.
             */
            $this->estadoEquipoService
                ->cambiarEstado(
                    equipoId:
                        $equipoSaliente->id,

                    codigoEstadoDestino:
                        'GARANTIA',

                    usuarioId:
                        $usuario->id,

                    autorizadoPorId:
                        $usuario->id,

                    motivo:
                        "Cambio por garantía {$caso->numero}",

                    observacion:
                        $motivo
                );

            /*
             * El reemplazo queda entregado al cliente.
             */
            $this->estadoEquipoService
                ->cambiarEstado(
                    equipoId:
                        $equipoEntrante->id,

                    codigoEstadoDestino:
                        'VENDIDO',

                    usuarioId:
                        $usuario->id,

                    autorizadoPorId:
                        null,

                    motivo:
                        "Equipo entregado por cambio de garantía {$caso->numero}",

                    observacion:
                        $motivo
                );

            /*
             * El cambio físico no cierra automáticamente el caso.
             * El cierre seguirá usando cerrarCaso().
             */
            if ($caso->estado === 'DIAGNOSTICADO') {
                $caso->estado =
                    'EN_PROCESO';

                $caso->save();
            }

            return $cambio->fresh([
                'casoGarantia',
                'equipoSaliente.estadoActual',
                'equipoEntrante.estadoActual',
                'autorizadoPor',
            ]);
        }, 3);
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

    private function obtenerUsuarioAutorizadorCambioActivo(
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
                'garantias.autorizar_cambio'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no cuenta con permiso para autorizar cambios de equipo.'
            );
        }

        return $usuario;
    }
}