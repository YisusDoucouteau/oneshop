<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\IntervencionUnidadAdquirida;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\TipoCosto;
use App\Models\UnidadAdquirida;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\MovimientoInventario;
use App\Models\TipoMovimientoInventario;
class IntervencionUnidadAdquiridaService
{
    public function __construct(
        private readonly TipoCambioService $tipoCambioService
    ) {
    }

    /**
     * Registra un componente comprado específicamente
     * para una unidad adquirida.
     *
     * Ejemplos:
     * - cargador
     * - SSD
     * - RAM
     *
     * No modifica existencias porque el componente
     * no proviene del stock de OneShop.
     */
    public function registrarComponenteExterno(
        int $usuarioId,
        int $unidadId,
        array $datos
    ): IntervencionUnidadAdquirida {
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

                $unidad =
                    $this->obtenerUnidadGestionable(
                        $unidadId
                    );

                $validator = Validator::make(
                    $datos,
                    [
                        'producto_id' => [
                            'required',
                            'integer',
                        ],

                        'cantidad' => [
                            'nullable',
                            'integer',
                            'min:1',
                        ],

                        'moneda_id' => [
                            'nullable',
                            'integer',
                        ],

                        'monto_origen' => [
                            'nullable',
                            'numeric',
                            'min:0',
                        ],

                        'tipo_cambio_aplicado' => [
                            'nullable',
                            'numeric',
                            'gt:0',
                        ],

                        'fecha' => [
                            'nullable',
                            'date',
                        ],

                        'descripcion' => [
                            'nullable',
                            'string',
                            'max:255',
                        ],

                        'referencia' => [
                            'nullable',
                            'string',
                            'max:150',
                        ],

                        'observacion' => [
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

                $producto =
                    Producto::query()
                        ->where(
                            'activo',
                            true
                        )
                        ->find(
                            $validados[
                                'producto_id'
                            ]
                        );

                if (!$producto) {
                    throw new ReglaNegocioException(
                        'El componente seleccionado no existe o se encuentra inactivo.'
                    );
                }

                $tipoCosto =
                    TipoCosto::query()
                        ->where(
                            'codigo',
                            'REPUESTO_EXTERNO'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if (!$tipoCosto) {
                    throw new ReglaNegocioException(
                        'No se encuentra configurado el tipo de costo REPUESTO_EXTERNO.'
                    );
                }

                $costo =
                    $this->resolverCosto(
                        usuario: $usuario,
                        monedaId:
                            $validados[
                                'moneda_id'
                            ]
                            ?? null,
                        montoOrigen:
                            $validados[
                                'monto_origen'
                            ]
                            ?? null,
                        tipoCambioAplicado:
                            $validados[
                                'tipo_cambio_aplicado'
                            ]
                            ?? null,
                        contexto:
                            "Componente externo para unidad adquirida #{$unidad->id}"
                    );

                $fecha =
                    isset($validados['fecha'])
                        ? Carbon::parse(
                            $validados['fecha']
                        )
                        : now();

                return IntervencionUnidadAdquirida::create([
                    'unidad_adquirida_id' =>
                        $unidad->id,

                    'tipo' =>
                        IntervencionUnidadAdquirida::TIPO_COMPONENTE,

                    'producto_id' =>
                        $producto->id,

                    'origen_componente' =>
                        IntervencionUnidadAdquirida::ORIGEN_COMPRA_EXTERNA,

                    'cantidad' =>
                        $validados['cantidad']
                        ?? 1,

                    'almacen_id' =>
                        $unidad->almacen_actual_id,

                    'movimiento_inventario_id' =>
                        null,

                    'tipo_costo_id' =>
                        $tipoCosto->id,

                    'moneda_id' =>
                        $costo['moneda_id'],

                    'tipo_cambio_id' =>
                        $costo['tipo_cambio_id'],

                    'monto_origen' =>
                        $costo['monto_origen'],

                    'monto_bob' =>
                        $costo['monto_bob'],

                    'fecha_inicio' =>
                        $fecha,

                    /*
                     * El componente se registra cuando
                     * ya fue adquirido/asignado.
                     */
                    'fecha_fin' =>
                        $fecha,

                    'descripcion' =>
                        $validados['descripcion']
                        ?? "Componente adquirido: {$producto->nombre}",

                    'resultado' =>
                        'Componente incorporado a la unidad.',

                    'referencia' =>
                        $validados['referencia']
                        ?? null,

                    'registrado_por_id' =>
                        $usuario->id,

                    'observacion' =>
                        $validados['observacion']
                        ?? null,
                ]);
            },
            3
        );
    }


    /**
     * Registra un servicio realizado antes de que
     * la máquina sea incorporada formalmente al inventario.
     *
     * Ejemplos:
     * - reparación BIOS
     * - instalación de sistema operativo
     * - mantenimiento
     * - mano de obra técnica
     */
    public function registrarServicio(
        int $usuarioId,
        int $unidadId,
        array $datos
    ): IntervencionUnidadAdquirida {
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

                $unidad =
                    $this->obtenerUnidadGestionable(
                        $unidadId
                    );

                $validator = Validator::make(
                    $datos,
                    [
                        'descripcion' => [
                            'required',
                            'string',
                            'max:255',
                        ],

                        'fecha_inicio' => [
                            'nullable',
                            'date',
                        ],

                        'fecha_fin' => [
                            'nullable',
                            'date',
                        ],

                        'moneda_id' => [
                            'nullable',
                            'integer',
                        ],

                        'monto_origen' => [
                            'nullable',
                            'numeric',
                            'min:0',
                        ],

                        'tipo_cambio_aplicado' => [
                            'nullable',
                            'numeric',
                            'gt:0',
                        ],

                        'resultado' => [
                            'nullable',
                            'string',
                        ],

                        'referencia' => [
                            'nullable',
                            'string',
                            'max:150',
                        ],

                        'observacion' => [
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

                $fechaInicio =
                    isset(
                        $validados['fecha_inicio']
                    )
                        ? Carbon::parse(
                            $validados[
                                'fecha_inicio'
                            ]
                        )
                        : now();

                $fechaFin =
                    isset(
                        $validados['fecha_fin']
                    )
                        ? Carbon::parse(
                            $validados[
                                'fecha_fin'
                            ]
                        )
                        : null;

                if (
                    $fechaFin
                    && $fechaFin->lt(
                        $fechaInicio
                    )
                ) {
                    throw ValidationException::withMessages([
                        'fecha_fin' =>
                            'La fecha de finalización no puede ser anterior a la fecha de inicio.',
                    ]);
                }

                $tipoCosto =
                    TipoCosto::query()
                        ->where(
                            'codigo',
                            'SERVICIO_EXTERNO'
                        )
                        ->where(
                            'activo',
                            true
                        )
                        ->first();

                if (!$tipoCosto) {
                    throw new ReglaNegocioException(
                        'No se encuentra configurado el tipo de costo SERVICIO_EXTERNO.'
                    );
                }

                $costo =
                    $this->resolverCosto(
                        usuario: $usuario,
                        monedaId:
                            $validados[
                                'moneda_id'
                            ]
                            ?? null,
                        montoOrigen:
                            $validados[
                                'monto_origen'
                            ]
                            ?? null,
                        tipoCambioAplicado:
                            $validados[
                                'tipo_cambio_aplicado'
                            ]
                            ?? null,
                        contexto:
                            "Servicio de unidad adquirida #{$unidad->id}"
                    );

                $intervencion =
                    IntervencionUnidadAdquirida::create([
                        'unidad_adquirida_id' =>
                            $unidad->id,

                        'tipo' =>
                            IntervencionUnidadAdquirida::TIPO_SERVICIO,

                        'producto_id' =>
                            null,

                        'origen_componente' =>
                            null,

                        'cantidad' =>
                            1,

                        'almacen_id' =>
                            $unidad->almacen_actual_id,

                        'movimiento_inventario_id' =>
                            null,

                        'tipo_costo_id' =>
                            $tipoCosto->id,

                        'moneda_id' =>
                            $costo['moneda_id'],

                        'tipo_cambio_id' =>
                            $costo['tipo_cambio_id'],

                        'monto_origen' =>
                            $costo['monto_origen'],

                        'monto_bob' =>
                            $costo['monto_bob'],

                        'fecha_inicio' =>
                            $fechaInicio,

                        'fecha_fin' =>
                            $fechaFin,

                        'descripcion' =>
                            trim(
                                $validados[
                                    'descripcion'
                                ]
                            ),

                        'resultado' =>
                            $validados['resultado']
                            ?? null,

                        'referencia' =>
                            $validados['referencia']
                            ?? null,

                        'registrado_por_id' =>
                            $usuario->id,

                        'observacion' =>
                            $validados['observacion']
                            ?? null,
                    ]);

                /*
                 * Mientras exista una preparación/servicio,
                 * la unidad no debe presentarse automáticamente
                 * como lista para despacho.
                 *
                 * Una nueva revisión preliminar confirmará
                 * posteriormente si ya está lista.
                 */
                $unidad->update([
                    'estado' =>
                        UnidadAdquirida::ESTADO_EN_PREPARACION,

                    'requiere_servicio' =>
                        true,

                    'servicio_requerido' =>
                        $intervencion->descripcion,

                    'fecha_lista_envio' =>
                        null,
                ]);

                return $intervencion->fresh([
                    'unidadAdquirida.producto',
                    'tipoCosto',
                    'moneda',
                    'tipoCambio',
                    'registradoPor',
                ]);
            },
            3
        );
    }
/**
 * Asigna a una unidad adquirida un componente
 * existente en el stock del almacén donde se
 * encuentra actualmente la unidad.
 *
 * La operación:
 * - bloquea la existencia;
 * - valida stock disponible;
 * - descuenta existencias;
 * - registra el movimiento;
 * - crea la intervención vinculada.
 *
 * Todo ocurre dentro de una sola transacción.
 */
public function asignarComponenteDesdeStock(
    int $usuarioId,
    int $unidadId,
    array $datos
): IntervencionUnidadAdquirida {
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

            $unidad =
                $this->obtenerUnidadGestionable(
                    $unidadId
                );

            $validator = Validator::make(
                $datos,
                [
                    'producto_id' => [
                        'required',
                        'integer',
                    ],

                    'cantidad' => [
                        'nullable',
                        'integer',
                        'min:1',
                    ],

                    'fecha' => [
                        'nullable',
                        'date',
                    ],

                    'descripcion' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'observacion' => [
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

            $cantidad =
                $validados['cantidad']
                ?? 1;

            /*
             * Bloqueamos también el producto para
             * evitar operar con uno modificado o
             * desactivado concurrentemente.
             */
            $producto =
                Producto::query()
                    ->lockForUpdate()
                    ->find(
                        $validados[
                            'producto_id'
                        ]
                    );

            if (!$producto) {
                throw new ReglaNegocioException(
                    'El componente seleccionado no existe.'
                );
            }

            if (!$producto->activo) {
                throw new ReglaNegocioException(
                    'El componente seleccionado se encuentra inactivo.'
                );
            }

            /*
             * El stock cuantitativo de
             * existencias_productos corresponde
             * a productos no serializados.
             */
            if ($producto->es_serializado) {
                throw new ReglaNegocioException(
                    'Un producto serializado no puede asignarse mediante existencias de componentes.'
                );
            }

            /*
             * La existencia se consulta en el almacén
             * donde físicamente se encuentra la unidad.
             */
            $existencia =
                DB::table(
                    'existencias_productos'
                )
                    ->where(
                        'producto_id',
                        $producto->id
                    )
                    ->where(
                        'almacen_id',
                        $unidad->almacen_actual_id
                    )
                    ->lockForUpdate()
                    ->first();

            if (!$existencia) {
                throw ValidationException::withMessages([
                    'producto_id' =>
                        'No existe stock registrado de este componente en el almacén actual de la unidad.',
                ]);
            }

            if (
                $existencia
                    ->cantidad_disponible
                < $cantidad
            ) {
                throw ValidationException::withMessages([
                    'cantidad' =>
                        "Stock insuficiente. Disponible: {$existencia->cantidad_disponible}.",
                ]);
            }

            $tipoMovimiento =
                TipoMovimientoInventario::query()
                    ->where(
                        'codigo',
                        'ASIGNACION_COMPONENTE'
                    )
                    ->where(
                        'activo',
                        true
                    )
                    ->first();

            if (!$tipoMovimiento) {
                throw new ReglaNegocioException(
                    'No se encuentra configurado el movimiento ASIGNACION_COMPONENTE.'
                );
            }

            $fecha =
                isset($validados['fecha'])
                    ? Carbon::parse(
                        $validados['fecha']
                    )
                    : now();

            /*
             * Primero registramos la intervención.
             * Todavía no tiene movimiento asociado.
             *
             * Si algo falla posteriormente, toda
             * la transacción será revertida.
             */
            $intervencion =
                IntervencionUnidadAdquirida::create([
                    'unidad_adquirida_id' =>
                        $unidad->id,

                    'tipo' =>
                        IntervencionUnidadAdquirida::TIPO_COMPONENTE,

                    'producto_id' =>
                        $producto->id,

                    'origen_componente' =>
                        IntervencionUnidadAdquirida::ORIGEN_STOCK,

                    'cantidad' =>
                        $cantidad,

                    'almacen_id' =>
                        $unidad->almacen_actual_id,

                    'movimiento_inventario_id' =>
                        null,

                    /*
                     * Por ahora no inventamos una
                     * valoración del componente.
                     *
                     * existencias_productos solamente
                     * almacena cantidades y no costo
                     * promedio/FIFO.
                     */
                    'tipo_costo_id' =>
                        null,

                    'moneda_id' =>
                        null,

                    'tipo_cambio_id' =>
                        null,

                    'monto_origen' =>
                        null,

                    'monto_bob' =>
                        null,

                    'fecha_inicio' =>
                        $fecha,

                    'fecha_fin' =>
                        $fecha,

                    'descripcion' =>
                        $validados['descripcion']
                        ?? "Componente asignado desde stock: {$producto->nombre}",

                    'resultado' =>
                        'Componente asignado desde existencias del almacén.',

                    'referencia' =>
                        null,

                    'registrado_por_id' =>
                        $usuario->id,

                    'observacion' =>
                        $validados['observacion']
                        ?? null,
                ]);

            $nuevoDisponible =
                $existencia
                    ->cantidad_disponible
                - $cantidad;

            /*
             * Las reservas no cambian.
             */
            $nuevoReservado =
                $existencia
                    ->cantidad_reservada;

            DB::table(
                'existencias_productos'
            )
                ->where(
                    'producto_id',
                    $producto->id
                )
                ->where(
                    'almacen_id',
                    $unidad->almacen_actual_id
                )
                ->update([
                    'cantidad_disponible' =>
                        $nuevoDisponible,

                    'updated_at' =>
                        now(),
                ]);

            $movimiento =
                MovimientoInventario::create([
                    'producto_id' =>
                        $producto->id,

                    'almacen_id' =>
                        $unidad->almacen_actual_id,

                    'tipo_movimiento_id' =>
                        $tipoMovimiento->id,

                    'usuario_id' =>
                        $usuario->id,

                    'cambio_disponible' =>
                        -$cantidad,

                    'cambio_reservado' =>
                        0,

                    'saldo_disponible_resultante' =>
                        $nuevoDisponible,

                    'saldo_reservado_resultante' =>
                        $nuevoReservado,

                    'tipo_referencia' =>
                        'INTERVENCION_UNIDAD_ADQUIRIDA',

                    'referencia_id' =>
                        $intervencion->id,

                    'fecha_movimiento' =>
                        $fecha,

                    'observacion' =>
                        "Asignación de {$cantidad} unidad(es) de {$producto->nombre} a unidad adquirida #{$unidad->id}.",
                ]);

            $intervencion->update([
                'movimiento_inventario_id' =>
                    $movimiento->id,
            ]);

            return $intervencion->fresh([
                'unidadAdquirida.producto',
                'producto',
                'almacen',
                'movimientoInventario.tipoMovimiento',
                'registradoPor',
            ]);
        },
        3
    );
}

    /**
     * Resuelve moneda, TC histórico y equivalente en Bs.
     *
     * USD y USDT se mantienen separados.
     * Nunca se presume que 1 USDT = 1 USD.
     */




    private function resolverCosto(
        User $usuario,
        ?int $monedaId,
        mixed $montoOrigen,
        mixed $tipoCambioAplicado,
        string $contexto
    ): array {
        if ($montoOrigen === null) {
            if (
                $monedaId !== null
                || $tipoCambioAplicado !== null
            ) {
                throw ValidationException::withMessages([
                    'monto_origen' =>
                        'No debe registrar moneda o tipo de cambio si no existe un costo.',
                ]);
            }

            return [
                'moneda_id' =>
                    null,

                'tipo_cambio_id' =>
                    null,

                'monto_origen' =>
                    null,

                'monto_bob' =>
                    null,
            ];
        }

        if ($monedaId === null) {
            throw ValidationException::withMessages([
                'moneda_id' =>
                    'Debe seleccionar la moneda del costo.',
            ]);
        }

        $moneda =
            Moneda::query()
                ->where(
                    'activo',
                    true
                )
                ->find(
                    $monedaId
                );

        if (!$moneda) {
            throw new ReglaNegocioException(
                'La moneda seleccionada no existe o se encuentra inactiva.'
            );
        }

        $monto =
            (float) $montoOrigen;

        if ($moneda->codigo === 'BOB') {
            if ($tipoCambioAplicado !== null) {
                throw ValidationException::withMessages([
                    'tipo_cambio_aplicado' =>
                        'No corresponde registrar tipo de cambio para un costo expresado en bolivianos.',
                ]);
            }

            return [
                'moneda_id' =>
                    $moneda->id,

                'tipo_cambio_id' =>
                    null,

                'monto_origen' =>
                    round(
                        $monto,
                        2
                    ),

                'monto_bob' =>
                    round(
                        $monto,
                        2
                    ),
            ];
        }

        if (
            !in_array(
                $moneda->codigo,
                [
                    'USD',
                    'USDT',
                ],
                true
            )
        ) {
            throw new ReglaNegocioException(
                'Actualmente los costos están habilitados para BOB, USD y USDT.'
            );
        }

        if ($tipoCambioAplicado === null) {
            throw ValidationException::withMessages([
                'tipo_cambio_aplicado' =>
                    "Debe registrar el tipo de cambio aplicado para {$moneda->codigo}.",
            ]);
        }

        $tipoCambio =
            $this->tipoCambioService
                ->registrarAplicado(
                    $usuario->id,
                    $moneda->codigo,
                    (float) $tipoCambioAplicado,
                    $contexto
                );

        $montoBob =
            $this->tipoCambioService
                ->convertirABob(
                    $monto,
                    $moneda->codigo,
                    $tipoCambio
                );

        return [
            'moneda_id' =>
                $moneda->id,

            'tipo_cambio_id' =>
                $tipoCambio->id,

            'monto_origen' =>
                round(
                    $monto,
                    2
                ),

            'monto_bob' =>
                round(
                    (float) $montoBob,
                    2
                ),
        ];
    }


    private function obtenerUnidadGestionable(
        int $unidadId
    ): UnidadAdquirida {
        $unidad =
            UnidadAdquirida::query()
                ->lockForUpdate()
                ->find(
                    $unidadId
                );

        if (!$unidad) {
            throw new ReglaNegocioException(
                'La unidad adquirida no existe.'
            );
        }

        if (
            $unidad->estaIncorporadaInventario()
            || in_array(
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
                'La unidad ya salió de la etapa de preparación previa al inventario.'
            );
        }

        return $unidad;
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
                ->find(
                    $usuarioId
                );

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