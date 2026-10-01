<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\Moneda;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CompraComponenteStockService
{
    public function __construct(
        private readonly TipoCambioService $tipoCambioService,
        private readonly ValoracionInventarioService $valoracionInventarioService
    ) {
    }

    /**
     * Registra una compra de un producto no serializado para stock.
     *
     * El monto ingresado corresponde al TOTAL de la compra.
     * El servicio:
     * - valida usuario, almacén, producto y moneda;
     * - conserva el tipo de cambio histórico para USD/USDT;
     * - calcula costo unitario de origen y BOB;
     * - registra una entrada valorizada;
     * - recalcula el promedio ponderado del stock.
     */
    public function registrarCompra(
        int $usuarioId,
        array $datos
    ): MovimientoInventario {
        return DB::transaction(
            function () use (
                $usuarioId,
                $datos
            ) {
                $usuario =
                    $this->obtenerUsuarioAutorizado(
                        $usuarioId
                    );

                $validator =
                    Validator::make(
                        $datos,
                        [
                            'producto_id' => [
                                'required',
                                'integer',
                            ],

                            'almacen_id' => [
                                'required',
                                'integer',
                            ],

                            'cantidad' => [
                                'required',
                                'integer',
                                'min:1',
                            ],

                            'moneda_id' => [
                                'required',
                                'integer',
                            ],

                            'monto_total_origen' => [
                                'required',
                                'numeric',
                                'gt:0',
                            ],

                            'tipo_cambio_aplicado' => [
                                'nullable',
                                'numeric',
                                'gt:0',
                            ],

                            'fecha_compra' => [
                                'nullable',
                                'date',
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

                if ($producto->es_serializado) {
                    throw new ReglaNegocioException(
                        'Los productos serializados no pueden ingresar mediante el stock cuantitativo de componentes.'
                    );
                }

                $almacen =
                    Almacen::query()
                        ->where(
                            'activo',
                            true
                        )
                        ->find(
                            $validados[
                                'almacen_id'
                            ]
                        );

                if (!$almacen) {
                    throw new ReglaNegocioException(
                        'El almacén seleccionado no existe o se encuentra inactivo.'
                    );
                }

                /*
                 * Si el usuario tiene una sede operativa asignada,
                 * no puede registrar stock en otra sede.
                 *
                 * Un usuario global sin almacén operativo puede
                 * seleccionar cualquier almacén activo.
                 */
                if (
                    $usuario->almacen_operativo_id !== null
                    &&
                    (int) $usuario->almacen_operativo_id
                        !== (int) $almacen->id
                ) {
                    throw new ReglaNegocioException(
                        'El usuario no puede registrar compras de stock en un almacén distinto a su almacén operativo.'
                    );
                }

                $moneda =
                    Moneda::query()
                        ->where(
                            'activo',
                            true
                        )
                        ->find(
                            $validados[
                                'moneda_id'
                            ]
                        );

                if (!$moneda) {
                    throw new ReglaNegocioException(
                        'La moneda seleccionada no existe o se encuentra inactiva.'
                    );
                }

                $cantidad =
                    (int)
                    $validados[
                        'cantidad'
                    ];

                $montoTotalOrigen =
                    round(
                        (float)
                        $validados[
                            'monto_total_origen'
                        ],
                        2
                    );

                $tipoCambioAplicado =
                    isset(
                        $validados[
                            'tipo_cambio_aplicado'
                        ]
                    )
                        ? (float)
                            $validados[
                                'tipo_cambio_aplicado'
                            ]
                        : null;

                $tipoCambioId = null;

                if ($moneda->codigo === 'BOB') {
                    if (
                        $tipoCambioAplicado
                        !== null
                    ) {
                        throw ValidationException::withMessages([
                            'tipo_cambio_aplicado' =>
                                'No corresponde registrar tipo de cambio para una compra expresada en bolivianos.',
                        ]);
                    }

                    $montoTotalBob =
                        $montoTotalOrigen;
                } elseif (
                    in_array(
                        $moneda->codigo,
                        [
                            'USD',
                            'USDT',
                        ],
                        true
                    )
                ) {
                    if (
                        $tipoCambioAplicado
                        === null
                    ) {
                        throw ValidationException::withMessages([
                            'tipo_cambio_aplicado' =>
                                "Debe registrar el tipo de cambio aplicado para {$moneda->codigo}.",
                        ]);
                    }

                    $tipoCambio =
                        $this
                            ->tipoCambioService
                            ->registrarAplicado(
                                $usuario->id,
                                $moneda->codigo,
                                $tipoCambioAplicado,
                                "Compra de componente para stock en almacén {$almacen->codigo}"
                            );

                    $tipoCambioId =
                        $tipoCambio->id;

                    $montoTotalBob =
                        $this
                            ->tipoCambioService
                            ->convertirABob(
                                $montoTotalOrigen,
                                $moneda->codigo,
                                $tipoCambio
                            );
                } else {
                    throw new ReglaNegocioException(
                        'Actualmente las compras de componentes están habilitadas para BOB, USD y USDT.'
                    );
                }

                $costoUnitarioOrigen =
                    round(
                        $montoTotalOrigen
                        /
                        $cantidad,
                        6
                    );

                $costoUnitarioBob =
                    round(
                        (float) $montoTotalBob
                        /
                        $cantidad,
                        6
                    );

                $fechaCompra =
                    isset(
                        $validados[
                            'fecha_compra'
                        ]
                    )
                        ? Carbon::parse(
                            $validados[
                                'fecha_compra'
                            ]
                        )
                        : now();

                $observacionMovimiento =
                    $this->construirObservacion(
                        $validados[
                            'referencia'
                        ]
                            ?? null,
                        $validados[
                            'observacion'
                        ]
                            ?? null
                    );

                return $this
                    ->valoracionInventarioService
                    ->registrarEntradaValorizada(
                        productoId:
                            $producto->id,

                        almacenId:
                            $almacen->id,

                        cantidad:
                            $cantidad,

                        costoUnitarioBob:
                            $costoUnitarioBob,

                        tipoMovimientoCodigo:
                            'COMPRA_LOCAL',

                        usuarioId:
                            $usuario->id,

                        tipoReferencia:
                            'COMPRA_STOCK_COMPONENTE',

                        referenciaId:
                            null,

                        observacion:
                            $observacionMovimiento,

                        monedaId:
                            $moneda->id,

                        tipoCambioId:
                            $tipoCambioId,

                        costoUnitarioOrigen:
                            $costoUnitarioOrigen,

                        costoTotalOrigen:
                            $montoTotalOrigen,

                        fechaMovimiento:
                            $fechaCompra
                    );
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
                'inventario.registrar'
            )
        ) {
            throw new ReglaNegocioException(
                'El usuario no tiene permiso para registrar movimientos de inventario.'
            );
        }

        return $usuario;
    }

    private function construirObservacion(
        ?string $referencia,
        ?string $observacion
    ): string {
        $partes = [
            'Compra de componente para stock.',
        ];

        $referencia =
            trim(
                (string) $referencia
            );

        $observacion =
            trim(
                (string) $observacion
            );

        if ($referencia !== '') {
            $partes[] =
                "Referencia: {$referencia}.";
        }

        if ($observacion !== '') {
            $partes[] =
                $observacion;
        }

        return implode(
            ' ',
            $partes
        );
    }
}
