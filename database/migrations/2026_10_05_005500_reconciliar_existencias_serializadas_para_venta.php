<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('equipos')
            || !Schema::hasTable('productos')
            || !Schema::hasTable('estados_equipos')
            || !Schema::hasTable('existencias_productos')
        ) {
            return;
        }

        $estados = DB::table('estados_equipos')
            ->whereIn('codigo', [
                'DISPONIBLE',
                'RESERVADO',
            ])
            ->pluck('id', 'codigo');

        $disponibleId = $estados->get('DISPONIBLE');
        $reservadoId = $estados->get('RESERVADO');

        if (!$disponibleId || !$reservadoId) {
            return;
        }

        /*
         * Reparación conservadora para datos ya existentes.
         *
         * Los equipos serializados son unidades físicas individuales. Si una
         * unidad ya está DISPONIBLE o RESERVADA, la existencia agregada del
         * producto/almacén no puede ser menor al número de equipos en esos
         * estados. No reducimos cantidades preexistentes en esta migración;
         * solamente corregimos faltantes creados antes de sincronizar el flujo
         * RECIBIDO -> DISPONIBLE con existencias_productos.
         */
        $grupos = DB::table('equipos as e')
            ->join(
                'productos as p',
                'p.id',
                '=',
                'e.producto_id'
            )
            ->where('p.es_serializado', true)
            ->where('e.activo', true)
            ->whereIn('e.estado_actual_id', [
                $disponibleId,
                $reservadoId,
            ])
            ->select([
                'e.producto_id',
                'e.almacen_actual_id',
            ])
            ->selectRaw(
                'SUM(CASE WHEN e.estado_actual_id = ? THEN 1 ELSE 0 END) AS equipos_disponibles',
                [$disponibleId]
            )
            ->selectRaw(
                'SUM(CASE WHEN e.estado_actual_id = ? THEN 1 ELSE 0 END) AS equipos_reservados',
                [$reservadoId]
            )
            ->groupBy(
                'e.producto_id',
                'e.almacen_actual_id'
            )
            ->get();

        foreach ($grupos as $grupo) {
            $existencia = DB::table('existencias_productos')
                ->where('producto_id', $grupo->producto_id)
                ->where('almacen_id', $grupo->almacen_actual_id)
                ->first();

            $disponible = max(
                (int) ($existencia?->cantidad_disponible ?? 0),
                (int) $grupo->equipos_disponibles
            );

            $reservado = max(
                (int) ($existencia?->cantidad_reservada ?? 0),
                (int) $grupo->equipos_reservados
            );

            if ($existencia) {
                DB::table('existencias_productos')
                    ->where('producto_id', $grupo->producto_id)
                    ->where('almacen_id', $grupo->almacen_actual_id)
                    ->update([
                        'cantidad_disponible' => $disponible,
                        'cantidad_reservada' => $reservado,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            DB::table('existencias_productos')
                ->insert([
                    'producto_id' => $grupo->producto_id,
                    'almacen_id' => $grupo->almacen_actual_id,
                    'cantidad_disponible' => $disponible,
                    'cantidad_reservada' => $reservado,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        /*
         * No se revierte una reparación de cantidades porque podría eliminar
         * movimientos reales ocurridos después de ejecutar la migración.
         */
    }
};
