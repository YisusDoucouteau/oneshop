<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\MovimientoInventario;
use App\Models\TipoMovimientoInventario;
use Illuminate\Support\Facades\DB;

class MovimientoInventarioService
{

    public function registrarEntrada(
        int $productoId,
        int $almacenId,
        int $cantidad,
        string $tipoCodigo,
        ?int $usuarioId = null,
        ?string $tipoReferencia = null,
        ?int $referenciaId = null,
        ?string $observacion = null
    ): MovimientoInventario {

        return DB::transaction(function () use (
            $productoId,
            $almacenId,
            $cantidad,
            $tipoCodigo,
            $usuarioId,
            $tipoReferencia,
            $referenciaId,
            $observacion
        ) {

            if ($cantidad <= 0) {
                throw new ReglaNegocioException(
                    'La cantidad debe ser mayor a cero.'
                );
            }


            $tipo = $this->obtenerTipoMovimiento(
                $tipoCodigo
            );


            $existencia = DB::table(
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


            $saldoAnterior =
                $existencia?->cantidad_disponible ?? 0;


            $saldoReservado =
                $existencia?->cantidad_reservada ?? 0;


            $nuevoSaldo =
                $saldoAnterior + $cantidad;


            DB::table('existencias_productos')
                ->updateOrInsert(
                    [
                        'producto_id' => $productoId,
                        'almacen_id' => $almacenId,
                    ],
                    [
                        'cantidad_disponible' => $nuevoSaldo,
                        'cantidad_reservada' => $saldoReservado,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );


            return MovimientoInventario::create([

                'producto_id' =>
                    $productoId,

                'almacen_id' =>
                    $almacenId,

                'tipo_movimiento_id' =>
                    $tipo->id,

                'usuario_id' =>
                    $usuarioId,

                'cambio_disponible' =>
                    $cantidad,

                'cambio_reservado' =>
                    0,

                'saldo_disponible_resultante' =>
                    $nuevoSaldo,

                'saldo_reservado_resultante' =>
                    $saldoReservado,

                'tipo_referencia' =>
                    $tipoReferencia,

                'referencia_id' =>
                    $referenciaId,

                'fecha_movimiento' =>
                    now(),

                'observacion' =>
                    $observacion,

            ]);

        });

    }



    public function registrarSalida(
        int $productoId,
        int $almacenId,
        int $cantidad,
        string $tipoCodigo,
        ?int $usuarioId = null,
        ?string $tipoReferencia = null,
        ?int $referenciaId = null,
        ?string $observacion = null
    ): MovimientoInventario {


        return DB::transaction(function () use (
            $productoId,
            $almacenId,
            $cantidad,
            $tipoCodigo,
            $usuarioId,
            $tipoReferencia,
            $referenciaId,
            $observacion
        ) {


            if ($cantidad <= 0) {

                throw new ReglaNegocioException(
                    'La cantidad debe ser mayor a cero.'
                );

            }


            $tipo = $this->obtenerTipoMovimiento(
                $tipoCodigo
            );


            $existencia = DB::table(
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


            $saldoActual =
                $existencia?->cantidad_disponible ?? 0;


            $saldoReservado =
                $existencia?->cantidad_reservada ?? 0;



            if ($saldoActual < $cantidad) {

                throw new ReglaNegocioException(
                    'No existe suficiente inventario disponible.'
                );

            }


            $nuevoSaldo =
                $saldoActual - $cantidad;



            DB::table('existencias_productos')
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
                        $nuevoSaldo,

                    'updated_at' =>
                        now(),

                ]);



            return MovimientoInventario::create([

                'producto_id' =>
                    $productoId,

                'almacen_id' =>
                    $almacenId,

                'tipo_movimiento_id' =>
                    $tipo->id,

                'usuario_id' =>
                    $usuarioId,

                'cambio_disponible' =>
                    -$cantidad,

                'cambio_reservado' =>
                    0,

                'saldo_disponible_resultante' =>
                    $nuevoSaldo,

                'saldo_reservado_resultante' =>
                    $saldoReservado,

                'tipo_referencia' =>
                    $tipoReferencia,

                'referencia_id' =>
                    $referenciaId,

                'fecha_movimiento' =>
                    now(),

                'observacion' =>
                    $observacion,

            ]);

        });

    }

    public function registrarReserva(
    int $productoId,
    int $almacenId,
    int $cantidad,
    int $usuarioId,
    ?string $tipoReferencia = null,
    ?int $referenciaId = null,
    ?string $observacion = null
): MovimientoInventario {


    return DB::transaction(function () use (
        $productoId,
        $almacenId,
        $cantidad,
        $usuarioId,
        $tipoReferencia,
        $referenciaId,
        $observacion
    ) {


        if ($cantidad <= 0) {

            throw new ReglaNegocioException(
                'La cantidad debe ser mayor a cero.'
            );

        }


        $tipo = $this->obtenerTipoMovimiento(
            'RESERVA'
        );



        $existencia = DB::table(
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



        $disponible =
            $existencia?->cantidad_disponible ?? 0;


        $reservado =
            $existencia?->cantidad_reservada ?? 0;



        if ($disponible < $cantidad) {

            throw new ReglaNegocioException(
                'No existe suficiente stock disponible para reservar.'
            );

        }



        $nuevoDisponible =
            $disponible - $cantidad;


        $nuevoReservado =
            $reservado + $cantidad;



        DB::table('existencias_productos')
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

                'cantidad_reservada' =>
                    $nuevoReservado,

                'updated_at' =>
                    now(),

            ]);



        return MovimientoInventario::create([

            'producto_id' =>
                $productoId,

            'almacen_id' =>
                $almacenId,

            'tipo_movimiento_id' =>
                $tipo->id,

            'usuario_id' =>
                $usuarioId,

            'cambio_disponible' =>
                -$cantidad,

            'cambio_reservado' =>
                $cantidad,

            'saldo_disponible_resultante' =>
                $nuevoDisponible,

            'saldo_reservado_resultante' =>
                $nuevoReservado,

            'tipo_referencia' =>
                $tipoReferencia,

            'referencia_id' =>
                $referenciaId,

            'fecha_movimiento' =>
                now(),

            'observacion' =>
                $observacion,

        ]);

    });

}

    private function obtenerTipoMovimiento(
        string $codigo
    ): TipoMovimientoInventario {


        $tipo = TipoMovimientoInventario::where(
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