<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\TipoMovimientoInventario;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class ValoracionInventarioService
{
    public function registrarEntradaValorizada(
        int $productoId,
        int $almacenId,
        int $cantidad,
        float $costoUnitarioBob,
        string $tipoMovimientoCodigo = 'ENTRADA',
        ?int $usuarioId = null,
        ?string $tipoReferencia = null,
        ?int $referenciaId = null,
        ?string $observacion = null,
        ?int $monedaId = null,
        ?int $tipoCambioId = null,
        ?float $costoUnitarioOrigen = null,
        ?float $costoTotalOrigen = null,
        ?CarbonInterface $fechaMovimiento = null
    ): MovimientoInventario {
        return DB::transaction(
            function () use (
                $productoId,
                $almacenId,
                $cantidad,
                $costoUnitarioBob,
                $tipoMovimientoCodigo,
                $usuarioId,
                $tipoReferencia,
                $referenciaId,
                $observacion,
                $monedaId,
                $tipoCambioId,
                $costoUnitarioOrigen,
                $costoTotalOrigen,
                $fechaMovimiento
            ) {
                $this->validarCantidad(
                    $cantidad
                );

                if ($costoUnitarioBob <= 0) {
                    throw new ReglaNegocioException(
                        'El costo unitario en bolivianos debe ser mayor a cero.'
                    );
                }

                $this->obtenerProductoValorizable(
                    $productoId
                );

                $this->obtenerAlmacenActivo(
                    $almacenId
                );

                $tipoMovimiento =
                    $this->obtenerTipoMovimiento(
                        $tipoMovimientoCodigo
                    );

                $existencia =
                    DB::table(
                        'existencias_productos'
                    )
                        ->where(
                            'producto_id',
                            $productoId
                        )
                        ->where(
                            'almacen_id',
                            $almacenId
                        )
                        ->lockForUpdate()
                        ->first();

                $disponibleAnterior =
                    (int) (
                        $existencia
                            ?->cantidad_disponible
                        ?? 0
                    );

                $reservadoAnterior =
                    (int) (
                        $existencia
                            ?->cantidad_reservada
                        ?? 0
                    );

                $stockFisicoAnterior =
                    $disponibleAnterior
                    +
                    $reservadoAnterior;

                $promedioAnterior =
                    $existencia
                        ?->costo_promedio_bob;

                if (
                    $stockFisicoAnterior > 0
                    &&
                    $promedioAnterior === null
                ) {
                    throw new ReglaNegocioException(
                        'La existencia actual tiene unidades sin valoración. Debe regularizar su costo antes de registrar una nueva entrada valorizada.'
                    );
                }

                $valorAnterior =
                    $stockFisicoAnterior
                    *
                    (float) (
                        $promedioAnterior
                        ?? 0
                    );

                $valorEntrada =
                    $cantidad
                    *
                    $costoUnitarioBob;

                $nuevoDisponible =
                    $disponibleAnterior
                    +
                    $cantidad;

                $nuevoStockFisico =
                    $stockFisicoAnterior
                    +
                    $cantidad;

                $nuevoPromedio =
                    round(
                        (
                            $valorAnterior
                            +
                            $valorEntrada
                        )
                        /
                        $nuevoStockFisico,
                        6
                    );

                if ($existencia) {
                    DB::table(
                        'existencias_productos'
                    )
                        ->where(
                            'producto_id',
                            $productoId
                        )
                        ->where(
                            'almacen_id',
                            $almacenId
                        )
                        ->update([
                            'cantidad_disponible' =>
                                $nuevoDisponible,

                            'costo_promedio_bob' =>
                                $nuevoPromedio,

                            'updated_at' =>
                                now(),
                        ]);
                } else {
                    DB::table(
                        'existencias_productos'
                    )
                        ->insert([
                            'producto_id' =>
                                $productoId,

                            'almacen_id' =>
                                $almacenId,

                            'cantidad_disponible' =>
                                $nuevoDisponible,

                            'cantidad_reservada' =>
                                0,

                            'costo_promedio_bob' =>
                                $nuevoPromedio,

                            'created_at' =>
                                now(),

                            'updated_at' =>
                                now(),
                        ]);
                }

                $totalBob =
                    round(
                        $valorEntrada,
                        2
                    );

                return MovimientoInventario::create([
                    'producto_id' =>
                        $productoId,

                    'almacen_id' =>
                        $almacenId,

                    'tipo_movimiento_id' =>
                        $tipoMovimiento->id,

                    'usuario_id' =>
                        $usuarioId,

                    'moneda_id' =>
                        $monedaId,

                    'tipo_cambio_id' =>
                        $tipoCambioId,

                    'costo_unitario_origen' =>
                        $costoUnitarioOrigen !== null
                            ? round(
                                $costoUnitarioOrigen,
                                6
                            )
                            : null,

                    'costo_total_origen' =>
                        $costoTotalOrigen !== null
                            ? round(
                                $costoTotalOrigen,
                                2
                            )
                            : null,

                    'costo_unitario_bob' =>
                        round(
                            $costoUnitarioBob,
                            6
                        ),

                    'costo_total_bob' =>
                        $totalBob,

                    'costo_promedio_resultante_bob' =>
                        $nuevoPromedio,

                    'cambio_disponible' =>
                        $cantidad,

                    'cambio_reservado' =>
                        0,

                    'saldo_disponible_resultante' =>
                        $nuevoDisponible,

                    'saldo_reservado_resultante' =>
                        $reservadoAnterior,

                    'tipo_referencia' =>
                        $tipoReferencia,

                    'referencia_id' =>
                        $referenciaId,

                    'fecha_movimiento' =>
                        $fechaMovimiento
                        ?? now(),

                    'observacion' =>
                        $observacion,
                ]);
            },
            3
        );
    }

    public function registrarSalidaValorizada(
        int $productoId,
        int $almacenId,
        int $cantidad,
        string $tipoMovimientoCodigo = 'SALIDA',
        ?int $usuarioId = null,
        ?string $tipoReferencia = null,
        ?int $referenciaId = null,
        ?string $observacion = null,
        ?CarbonInterface $fechaMovimiento = null
    ): MovimientoInventario {
        return DB::transaction(
            function () use (
                $productoId,
                $almacenId,
                $cantidad,
                $tipoMovimientoCodigo,
                $usuarioId,
                $tipoReferencia,
                $referenciaId,
                $observacion,
                $fechaMovimiento
            ) {
                $this->validarCantidad(
                    $cantidad
                );

                $this->obtenerProductoValorizable(
                    $productoId
                );

                $this->obtenerAlmacenActivo(
                    $almacenId
                );

                $tipoMovimiento =
                    $this->obtenerTipoMovimiento(
                        $tipoMovimientoCodigo
                    );

                $existencia =
                    DB::table(
                        'existencias_productos'
                    )
                        ->where(
                            'producto_id',
                            $productoId
                        )
                        ->where(
                            'almacen_id',
                            $almacenId
                        )
                        ->lockForUpdate()
                        ->first();

                if (!$existencia) {
                    throw new ReglaNegocioException(
                        'No existe stock registrado para el producto en el almacén.'
                    );
                }

                $disponible =
                    (int)
                    $existencia
                        ->cantidad_disponible;

                $reservado =
                    (int)
                    $existencia
                        ->cantidad_reservada;

                if ($disponible < $cantidad) {
                    throw new ReglaNegocioException(
                        "Stock insuficiente. Disponible: {$disponible}."
                    );
                }

                if (
                    $existencia
                        ->costo_promedio_bob
                    === null
                ) {
                    throw new ReglaNegocioException(
                        'El stock seleccionado no tiene costo promedio registrado.'
                    );
                }

                $promedio =
                    (float)
                    $existencia
                        ->costo_promedio_bob;

                $nuevoDisponible =
                    $disponible
                    -
                    $cantidad;

                DB::table(
                    'existencias_productos'
                )
                    ->where(
                        'producto_id',
                        $productoId
                    )
                    ->where(
                        'almacen_id',
                        $almacenId
                    )
                    ->update([
                        'cantidad_disponible' =>
                            $nuevoDisponible,

                        'costo_promedio_bob' =>
                            $promedio,

                        'updated_at' =>
                            now(),
                    ]);

                $totalBob =
                    round(
                        $promedio
                        *
                        $cantidad,
                        2
                    );

                return MovimientoInventario::create([
                    'producto_id' =>
                        $productoId,

                    'almacen_id' =>
                        $almacenId,

                    'tipo_movimiento_id' =>
                        $tipoMovimiento->id,

                    'usuario_id' =>
                        $usuarioId,

                    'moneda_id' =>
                        null,

                    'tipo_cambio_id' =>
                        null,

                    'costo_unitario_origen' =>
                        null,

                    'costo_total_origen' =>
                        null,

                    'costo_unitario_bob' =>
                        round(
                            $promedio,
                            6
                        ),

                    'costo_total_bob' =>
                        $totalBob,

                    'costo_promedio_resultante_bob' =>
                        round(
                            $promedio,
                            6
                        ),

                    'cambio_disponible' =>
                        -$cantidad,

                    'cambio_reservado' =>
                        0,

                    'saldo_disponible_resultante' =>
                        $nuevoDisponible,

                    'saldo_reservado_resultante' =>
                        $reservado,

                    'tipo_referencia' =>
                        $tipoReferencia,

                    'referencia_id' =>
                        $referenciaId,

                    'fecha_movimiento' =>
                        $fechaMovimiento
                        ?? now(),

                    'observacion' =>
                        $observacion,
                ]);
            },
            3
        );
    }

    private function validarCantidad(
        int $cantidad
    ): void {
        if ($cantidad <= 0) {
            throw new ReglaNegocioException(
                'La cantidad debe ser mayor a cero.'
            );
        }
    }

    private function obtenerProductoValorizable(
        int $productoId
    ): Producto {
        $producto =
            Producto::query()
                ->lockForUpdate()
                ->find(
                    $productoId
                );

        if (!$producto) {
            throw new ReglaNegocioException(
                'El producto no existe.'
            );
        }

        if (!$producto->activo) {
            throw new ReglaNegocioException(
                'El producto se encuentra inactivo.'
            );
        }

        if ($producto->es_serializado) {
            throw new ReglaNegocioException(
                'Los productos serializados no se gestionan mediante existencias cuantitativas.'
            );
        }

        return $producto;
    }

    private function obtenerAlmacenActivo(
        int $almacenId
    ): Almacen {
        $almacen =
            Almacen::query()
                ->find(
                    $almacenId
                );

        if (
            !$almacen
            ||
            !$almacen->activo
        ) {
            throw new ReglaNegocioException(
                'El almacén no existe o se encuentra inactivo.'
            );
        }

        return $almacen;
    }

    private function obtenerTipoMovimiento(
        string $codigo
    ): TipoMovimientoInventario {
        $tipo =
            TipoMovimientoInventario::query()
                ->where(
                    'codigo',
                    $codigo
                )
                ->where(
                    'activo',
                    true
                )
                ->first();

        if (!$tipo) {
            throw new ReglaNegocioException(
                "Tipo de movimiento {$codigo} no existe."
            );
        }

        return $tipo;
    }
}
