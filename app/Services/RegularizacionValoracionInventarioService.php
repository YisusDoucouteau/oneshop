<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\RegularizacionValoracionInventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegularizacionValoracionInventarioService
{
    public function regularizar(
        int $usuarioId,
        int $productoId,
        int $almacenId,
        float $costoUnitarioBob,
        string $referencia,
        string $motivo
    ): RegularizacionValoracionInventario {
        return DB::transaction(
            function () use (
                $usuarioId,
                $productoId,
                $almacenId,
                $costoUnitarioBob,
                $referencia,
                $motivo
            ) {
                if ($costoUnitarioBob <= 0) {
                    throw new ReglaNegocioException(
                        'El costo unitario en bolivianos debe ser mayor a cero.'
                    );
                }

                $referencia = trim($referencia);
                $motivo = trim($motivo);

                if ($referencia === '') {
                    throw new ReglaNegocioException(
                        'Debe registrar una referencia para la regularización.'
                    );
                }

                if ($motivo === '') {
                    throw new ReglaNegocioException(
                        'Debe registrar el motivo de la regularización.'
                    );
                }

                $usuario = User::query()->find($usuarioId);

                if (!$usuario) {
                    throw new ReglaNegocioException(
                        'El usuario que realiza la regularización no existe.'
                    );
                }

                $producto = Producto::query()
                    ->lockForUpdate()
                    ->find($productoId);

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
                        'Los productos serializados no se regularizan mediante existencias cuantitativas.'
                    );
                }

                $almacen = Almacen::query()
                    ->find($almacenId);

                if (!$almacen || !$almacen->activo) {
                    throw new ReglaNegocioException(
                        'El almacén no existe o se encuentra inactivo.'
                    );
                }

                if (
                    $usuario->almacen_operativo_id !== null
                    && (int) $usuario->almacen_operativo_id !== $almacenId
                ) {
                    throw new ReglaNegocioException(
                        'No puede regularizar stock de un almacén distinto a su almacén operativo.'
                    );
                }

                $existencia = DB::table('existencias_productos')
                    ->where('producto_id', $productoId)
                    ->where('almacen_id', $almacenId)
                    ->lockForUpdate()
                    ->first();

                if (!$existencia) {
                    throw new ReglaNegocioException(
                        'No existe stock registrado para el producto en el almacén.'
                    );
                }

                $disponible = (int) $existencia->cantidad_disponible;
                $reservado = (int) $existencia->cantidad_reservada;
                $stockFisico = $disponible + $reservado;

                if ($stockFisico <= 0) {
                    throw new ReglaNegocioException(
                        'La existencia no tiene stock físico para regularizar.'
                    );
                }

                if ($existencia->costo_promedio_bob !== null) {
                    throw new ReglaNegocioException(
                        'La existencia ya tiene una valoración registrada.'
                    );
                }

                $regularizacionExistente =
                    RegularizacionValoracionInventario::query()
                        ->where('producto_id', $productoId)
                        ->where('almacen_id', $almacenId)
                        ->lockForUpdate()
                        ->exists();

                if ($regularizacionExistente) {
                    throw new ReglaNegocioException(
                        'La existencia ya fue regularizada anteriormente.'
                    );
                }

                $costoPromedio = round($costoUnitarioBob, 6);
                $valorTotal = round($stockFisico * $costoPromedio, 2);
                $fecha = now();

                DB::table('existencias_productos')
                    ->where('producto_id', $productoId)
                    ->where('almacen_id', $almacenId)
                    ->update([
                        'costo_promedio_bob' => $costoPromedio,
                        'updated_at' => $fecha,
                    ]);

                return RegularizacionValoracionInventario::query()->create([
                    'producto_id' => $productoId,
                    'almacen_id' => $almacenId,
                    'usuario_id' => $usuarioId,
                    'cantidad_disponible_snapshot' => $disponible,
                    'cantidad_reservada_snapshot' => $reservado,
                    'stock_fisico_snapshot' => $stockFisico,
                    'costo_promedio_anterior_bob' => null,
                    'costo_promedio_resultante_bob' => $costoPromedio,
                    'valor_total_bob' => $valorTotal,
                    'referencia' => $referencia,
                    'motivo' => $motivo,
                    'fecha_regularizacion' => $fecha,
                ]);
            },
            3
        );
    }
}
