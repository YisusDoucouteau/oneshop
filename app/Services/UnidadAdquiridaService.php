<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\DetalleLote;
use App\Models\EventoLogisticoLote;
use App\Models\TipoEventoLogistico;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UnidadAdquiridaService
{
    /**
     * Registra una llegada parcial de unidades físicas
     * correspondientes a una línea de compra.
     *
     * Ejemplo:
     * - Hugo compró 10 Dell.
     * - Hoy llegaron 3 al depósito de Cochabamba.
     * - Se crean 3 unidades adquiridas.
     *
     * NO modifica cantidad_recibida del detalle,
     * porque esa cantidad corresponde a la recepción
     * posterior en Oruro.
     */
    public function registrarLlegadaCochabamba(
        int $usuarioId,
        int $detalleLoteId,
        int $cantidad,
        ?string $fechaLlegada = null,
        ?string $observacion = null
    ): Collection {
        return DB::transaction(
            function () use (
                $usuarioId,
                $detalleLoteId,
                $cantidad,
                $fechaLlegada,
                $observacion
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                if ($cantidad < 1) {
                    throw ValidationException::withMessages([
                        'cantidad' =>
                            'La cantidad recibida en Cochabamba debe ser al menos 1.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Línea de compra
                |--------------------------------------------------------------------------
                */

                $detalle =
                    DetalleLote::query()
                        ->with([
                            'lote',
                            'producto',
                        ])
                        ->lockForUpdate()
                        ->find($detalleLoteId);

                if (!$detalle) {
                    throw new ReglaNegocioException(
                        'La línea de compra indicada no existe.'
                    );
                }

                if (
                    in_array(
                        $detalle->lote->estado,
                        [
                            'CANCELADO',
                            'CERRADO',
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'No se pueden registrar llegadas en un lote cerrado o cancelado.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Unidades ya registradas físicamente
                |--------------------------------------------------------------------------
                |
                | Se cuentan todas las unidades creadas desde esta línea,
                | independientemente de que después hayan viajado a Oruro.
                |
                */

                $registradas =
                    UnidadAdquirida::query()
                        ->where(
                            'detalle_lote_id',
                            $detalle->id
                        )
                        ->lockForUpdate()
                        ->count();

                $pendientes =
                    $detalle->cantidad_esperada
                    - $registradas;

                if ($pendientes <= 0) {
                    throw new ReglaNegocioException(
                        'Todas las unidades esperadas de esta línea ya fueron registradas físicamente.'
                    );
                }

                if ($cantidad > $pendientes) {
                    throw ValidationException::withMessages([
                        'cantidad' =>
                            "Solo quedan {$pendientes} unidad(es) pendientes de llegada para esta línea.",
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Depósito Cochabamba
                |--------------------------------------------------------------------------
                */

                $almacenCochabamba =
                    Almacen::query()
                        ->where(
                            'codigo',
                            'COCHABAMBA'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if (!$almacenCochabamba) {
                    throw new ReglaNegocioException(
                        'No se encuentra disponible el depósito de Cochabamba.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Fecha real de llegada
                |--------------------------------------------------------------------------
                |
                | Esta fecha representa el ingreso físico de la unidad
                | al depósito de Cochabamba.
                |
                | También será utilizada como parte del código
                | temporal de trazabilidad.
                |
                */

                $fecha =
                    $fechaLlegada
                        ? Carbon::parse(
                            $fechaLlegada
                        )
                        : now();

                /*
                |--------------------------------------------------------------------------
                | Reservar correlativos de trazabilidad
                |--------------------------------------------------------------------------
                |
                | El código visible nace cuando la unidad llega físicamente
                | a Cochabamba.
                |
                | Formato:
                |
                | OS-YYMMDD-NNNN
                |
                | Ejemplo:
                |
                | OS-260827-0023
                |
                | No corresponde al serial del fabricante ni al código
                | interno comercial que posteriormente se asigna en Oruro.
                |
                | Se reserva el bloque completo de correlativos dentro
                | de la misma transacción para evitar códigos duplicados.
                |
                */

                $fechaCorrelativo =
                    $fecha
                        ->copy()
                        ->startOfDay()
                        ->toDateString();

                /*
                 * Si todavía no existe contador para esta fecha,
                 * se crea inicialmente en cero.
                 *
                 * insertOrIgnore evita error si otra transacción
                 * intenta crear simultáneamente la misma fecha.
                 */
                DB::table(
                    'correlativos_trazabilidad_unidades'
                )->insertOrIgnore([
                    'fecha' =>
                        $fechaCorrelativo,

                    'ultimo_correlativo' =>
                        0,

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ]);

                /*
                 * Se bloquea el contador del día durante esta
                 * transacción para impedir que dos procesos
                 * reserven el mismo rango.
                 */
                $correlativo =
                    DB::table(
                        'correlativos_trazabilidad_unidades'
                    )
                        ->where(
                            'fecha',
                            $fechaCorrelativo
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$correlativo) {
                    throw new ReglaNegocioException(
                        'No fue posible obtener el correlativo de trazabilidad.'
                    );
                }

                $primerCorrelativo =
                    ((int) $correlativo->ultimo_correlativo)
                    + 1;

                $ultimoCorrelativo =
                    $primerCorrelativo
                    + $cantidad
                    - 1;

                /*
                 * El formato utiliza cuatro posiciones.
                 * Por tanto admite hasta 9.999 registros
                 * físicos en una misma fecha.
                 */
                if ($ultimoCorrelativo > 9999) {
                    throw new ReglaNegocioException(
                        'Se alcanzó el límite diario de códigos de trazabilidad.'
                    );
                }

                /*
                 * Se reserva anticipadamente todo el rango
                 * correspondiente a esta llegada.
                 */
                DB::table(
                    'correlativos_trazabilidad_unidades'
                )
                    ->where(
                        'fecha',
                        $fechaCorrelativo
                    )
                    ->update([
                        'ultimo_correlativo' =>
                            $ultimoCorrelativo,

                        'updated_at' =>
                            now(),
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Crear unidades físicas
                |--------------------------------------------------------------------------
                */

                $unidades =
                    collect();

                for (
                    $indice = 0;
                    $indice < $cantidad;
                    $indice++
                ) {
                    $numeroCorrelativo =
                        $primerCorrelativo
                        + $indice;

                    $codigoTrazabilidad =
                        sprintf(
                            'OS-%s-%04d',
                            $fecha->format('ymd'),
                            $numeroCorrelativo
                        );

                    $unidad =
                        UnidadAdquirida::create([
                            'detalle_lote_id' =>
                                $detalle->id,

                            'adquisicion_directa_id' =>
                                null,

                            'producto_id' =>
                                $detalle->producto_id,

                            'almacen_actual_id' =>
                                $almacenCochabamba->id,

                            'estado' =>
                                UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,

                            'codigo_trazabilidad' =>
                                $codigoTrazabilidad,

                            'fecha_llegada' =>
                                $fecha,

                            'registrado_por_id' =>
                                $usuario->id,

                            'requiere_servicio' =>
                                false,

                            'observacion_revision' =>
                                $observacion,
                        ]);

                    $unidades->push(
                        $unidad
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Evento general del lote
                |--------------------------------------------------------------------------
                |
                | El evento no reemplaza el detalle por unidad.
                | Solo deja constancia de que ocurrió una llegada parcial.
                |
                */

                $tipoEvento =
                    TipoEventoLogistico::query()
                        ->where(
                            'codigo',
                            'RECEPCION_COCHABAMBA'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if ($tipoEvento) {
                    EventoLogisticoLote::create([
                        'lote_id' =>
                            $detalle->lote_id,

                        'tipo_evento_logistico_id' =>
                            $tipoEvento->id,

                        'usuario_id' =>
                            $usuario->id,

                        'fecha_evento' =>
                            $fecha,

                        'ubicacion' =>
                            'Depósito Cochabamba',

                        'descripcion' =>
                            $this->descripcionLlegada(
                                $detalle,
                                $cantidad,
                                $registradas + $cantidad,
                                $observacion
                            ),
                    ]);
                }

                return $unidades;
            },
            3
        );
    }

    private function descripcionLlegada(
        DetalleLote $detalle,
        int $cantidadLlegada,
        int $totalRegistradas,
        ?string $observacion
    ): string {
        $descripcion =
            "{$cantidadLlegada} unidad(es) de "
            . "{$detalle->producto->nombre} "
            . "registrada(s) en Cochabamba. "
            . "Total físico registrado para la línea: "
            . "{$totalRegistradas}/"
            . "{$detalle->cantidad_esperada}.";

        if (
            $observacion !== null
            && trim($observacion) !== ''
        ) {
            $descripcion .=
                ' Observación: '
                . trim($observacion);
        }

        return $descripcion;
    }

    public function registrarRevisionPreliminar(
        int $usuarioId,
        int $unidadId,
        array $datos
    ): UnidadAdquirida {
        return DB::transaction(
            function () use (
                $usuarioId,
                $unidadId,
                $datos
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                /*
                |--------------------------------------------------------------------------
                | Unidad
                |--------------------------------------------------------------------------
                */

                $unidad =
                    UnidadAdquirida::query()
                        ->lockForUpdate()
                        ->find($unidadId);

                if (!$unidad) {
                    throw new ReglaNegocioException(
                        'La unidad adquirida no existe.'
                    );
                }

                /*
                 * La revisión preliminar corresponde a la etapa
                 * previa al despacho hacia Oruro.
                 */
                if (
                    in_array(
                        $unidad->estado,
                        [
                            UnidadAdquirida::ESTADO_ENVIADA,
                            UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                            UnidadAdquirida::ESTADO_INCORPORADA,
                        ],
                        true
                    )
                ) {
                    throw new ReglaNegocioException(
                        'La unidad ya salió de la etapa de revisión preliminar.'
                    );
                }

                $almacenCochabamba =
                    Almacen::query()
                        ->where(
                            'codigo',
                            'COCHABAMBA'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if (!$almacenCochabamba) {
                    throw new ReglaNegocioException(
                        'No se encuentra disponible el depósito de Cochabamba.'
                    );
                }

                if (
                    $unidad->almacen_actual_id
                    !== $almacenCochabamba->id
                ) {
                    throw new ReglaNegocioException(
                        'La revisión preliminar solo puede realizarse mientras la unidad se encuentra en Cochabamba.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validación
                |--------------------------------------------------------------------------
                */

                $validator =
                    Validator::make(
                        $datos,
                        [
                            'procesador' => [
                                'nullable',
                                'string',
                                'max:150',
                            ],

                            'generacion_procesador' => [
                                'nullable',
                                'string',
                                'max:80',
                            ],

                            'ram_gb' => [
                                'nullable',
                                'integer',
                                'min:0',
                                'max:65535',
                            ],

                            'almacenamiento_gb' => [
                                'nullable',
                                'integer',
                                'min:0',
                            ],

                            'tipo_almacenamiento' => [
                                'nullable',
                                'string',
                                'max:50',
                            ],

                            'tarjeta_grafica' => [
                                'nullable',
                                'string',
                                'max:150',
                            ],

                            'pantalla_pulgadas' => [
                                'nullable',
                                'numeric',
                                'min:0',
                                'max:999.9',
                            ],

                            'resolucion' => [
                                'nullable',
                                'string',
                                'max:50',
                            ],

                            'sistema_operativo' => [
                                'nullable',
                                'string',
                                'max:100',
                            ],

                            'serial_fabricante' => [
                                'nullable',
                                'string',
                                'max:150',
                            ],

                            'enciende' => [
                                'nullable',
                                'boolean',
                            ],

                            'tiene_sistema_operativo' => [
                                'nullable',
                                'boolean',
                            ],

                            'tiene_cargador' => [
                                'nullable',
                                'boolean',
                            ],

                            'requiere_servicio' => [
                                'nullable',
                                'boolean',
                            ],

                            'servicio_requerido' => [
                                'nullable',
                                'string',
                            ],

                            'observacion_revision' => [
                                'nullable',
                                'string',
                            ],
                        ]
                    );

                if ($validator->fails()) {
                    throw new ValidationException(
                        $validator
                    );
                }

                $validados =
                    $validator->validated();

                /*
                |--------------------------------------------------------------------------
                | Regla de preparación
                |--------------------------------------------------------------------------
                |
                | Hugo no hace aquí la inspección profunda de Oruro.
                |
                | Solo necesitamos saber si la unidad está suficientemente
                | funcional para continuar hacia Oruro:
                |
                | - enciende
                | - tiene sistema operativo
                | - tiene cargador
                | - no requiere servicio pendiente
                |
                */

                $enciende =
                    array_key_exists(
                        'enciende',
                        $validados
                    )
                        ? $validados['enciende']
                        : $unidad->enciende;

                $tieneSistema =
                    array_key_exists(
                        'tiene_sistema_operativo',
                        $validados
                    )
                        ? $validados[
                            'tiene_sistema_operativo'
                        ]
                        : $unidad
                            ->tiene_sistema_operativo;

                $tieneCargador =
                    array_key_exists(
                        'tiene_cargador',
                        $validados
                    )
                        ? $validados[
                            'tiene_cargador'
                        ]
                        : $unidad->tiene_cargador;

                $requiereServicio =
                    array_key_exists(
                        'requiere_servicio',
                        $validados
                    )
                        ? $validados[
                            'requiere_servicio'
                        ]
                        : $unidad
                            ->requiere_servicio;

                /*
                 * Si se marca servicio requerido,
                 * debe existir una explicación.
                 */
                $servicioRequerido =
                    $validados[
                        'servicio_requerido'
                    ]
                    ?? $unidad->servicio_requerido;

                if (
                    $requiereServicio
                    && (
                        $servicioRequerido === null
                        || trim(
                            $servicioRequerido
                        ) === ''
                    )
                ) {
                    throw ValidationException::withMessages([
                        'servicio_requerido' =>
                            'Debe indicar qué servicio o preparación necesita la unidad.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Determinar estado
                |--------------------------------------------------------------------------
                */

                $listaEnvio =
                    $enciende === true
                    && $tieneSistema === true
                    && $tieneCargador === true
                    && $requiereServicio === false;

                $tieneProblemaConocido =
                    $requiereServicio === true
                    || $enciende === false
                    || $tieneSistema === false
                    || $tieneCargador === false;

                if ($listaEnvio) {
                    $estado =
                        UnidadAdquirida::ESTADO_LISTA_ENVIO;

                    $fechaListaEnvio =
                        $unidad->fecha_lista_envio
                        ?? now();
                } elseif ($tieneProblemaConocido) {
                    $estado =
                        UnidadAdquirida::ESTADO_EN_PREPARACION;

                    $fechaListaEnvio =
                        null;
                } else {
                    $estado =
                        UnidadAdquirida::ESTADO_EN_REVISION;

                    $fechaListaEnvio =
                        null;
                }

                /*
                |--------------------------------------------------------------------------
                | Actualización
                |--------------------------------------------------------------------------
                */

                $unidad->fill([
                    'serial_fabricante' =>
                        $validados[
                            'serial_fabricante'
                        ]
                        ?? $unidad
                            ->serial_fabricante,

                    'procesador' =>
                        $validados['procesador']
                        ?? $unidad->procesador,

                    'generacion_procesador' =>
                        $validados[
                            'generacion_procesador'
                        ]
                        ?? $unidad
                            ->generacion_procesador,

                    'ram_gb' =>
                        $validados['ram_gb']
                        ?? $unidad->ram_gb,

                    'almacenamiento_gb' =>
                        $validados[
                            'almacenamiento_gb'
                        ]
                        ?? $unidad
                            ->almacenamiento_gb,

                    'tipo_almacenamiento' =>
                        $validados[
                            'tipo_almacenamiento'
                        ]
                        ?? $unidad
                            ->tipo_almacenamiento,

                    'tarjeta_grafica' =>
                        $validados[
                            'tarjeta_grafica'
                        ]
                        ?? $unidad
                            ->tarjeta_grafica,

                    'pantalla_pulgadas' =>
                        $validados[
                            'pantalla_pulgadas'
                        ]
                        ?? $unidad
                            ->pantalla_pulgadas,

                    'resolucion' =>
                        $validados['resolucion']
                        ?? $unidad->resolucion,

                    'sistema_operativo' =>
                        $validados[
                            'sistema_operativo'
                        ]
                        ?? $unidad
                            ->sistema_operativo,

                    'enciende' =>
                        $enciende,

                    'tiene_sistema_operativo' =>
                        $tieneSistema,

                    'tiene_cargador' =>
                        $tieneCargador,

                    'requiere_servicio' =>
                        $requiereServicio,

                    'servicio_requerido' =>
                        $requiereServicio
                            ? $servicioRequerido
                            : null,

                    'observacion_revision' =>
                        $validados[
                            'observacion_revision'
                        ]
                        ?? $unidad
                            ->observacion_revision,

                    'estado' =>
                        $estado,

                    'fecha_revision' =>
                        now(),

                    'fecha_lista_envio' =>
                        $fechaListaEnvio,

                    'revisado_por_id' =>
                        $usuario->id,
                ]);

                $unidad->save();

                return $unidad->fresh([
                    'producto.marca',
                    'almacenActual',
                    'detalleLote.lote',
                    'revisadoPor',
                ]);
            },
            3
        );
    }

    private function obtenerUsuarioAutorizado(
        int $usuarioId
    ): User {
        $usuario =
            User::query()
                ->where(
                    'activo',
                    true
                )
                ->find($usuarioId);

        if (!$usuario) {
            throw new ReglaNegocioException(
                'El usuario no existe o se encuentra inactivo.'
            );
        }

        if (
            !$usuario->tienePermiso(
                'importacion.gestionar'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no tiene permiso para gestionar importaciones.'
            );
        }

        return $usuario;
    }
}