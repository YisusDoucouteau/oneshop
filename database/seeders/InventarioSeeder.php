<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventarioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['RECEPCION_LOTE', 'Recepción de lote'],
            ['COMPRA_LOCAL', 'Compra local'],
            ['ASIGNACION_COMPONENTE', 'Asignación de componente'],
            ['RETIRO_COMPONENTE', 'Retiro de componente'],
            ['RESERVA', 'Reserva'],
            ['LIBERACION_RESERVA', 'Liberación de reserva'],
            ['VENTA_DIRECTA', 'Venta directa'],
            ['VENTA_RESERVADA', 'Venta proveniente de reserva'],
            ['TRANSFERENCIA_SALIDA', 'Salida por transferencia'],
            ['TRANSFERENCIA_ENTRADA', 'Entrada por transferencia'],
            ['DEVOLUCION', 'Devolución'],
            ['AJUSTE_ENTRADA', 'Ajuste de entrada'],
            ['AJUSTE_SALIDA', 'Ajuste de salida'],
        ];

        foreach ($tipos as [$codigo, $nombre]) {
            DB::table('tipos_movimientos_inventario')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}