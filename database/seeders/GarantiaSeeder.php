<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GarantiaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = DB::table('categorias_productos')
            ->pluck('id', 'codigo');

        $politicas = [
            [
                'codigo' => 'GARANTIA_LAPTOP_6M',
                'nombre' => 'Garantía de laptops',
                'categoria' => 'LAPTOP',
                'meses' => 6,
            ],
            [
                'codigo' => 'GARANTIA_PC_6M',
                'nombre' => 'Garantía de computadoras de escritorio',
                'categoria' => 'PC_ESCRITORIO',
                'meses' => 6,
            ],
            [
                'codigo' => 'GARANTIA_AIO_6M',
                'nombre' => 'Garantía de equipos All in One',
                'categoria' => 'ALL_IN_ONE',
                'meses' => 6,
            ],
            [
                'codigo' => 'GARANTIA_MINIPC_6M',
                'nombre' => 'Garantía de Mini PC',
                'categoria' => 'MINI_PC',
                'meses' => 6,
            ],
            [
                'codigo' => 'GARANTIA_ACCESORIOS_3M',
                'nombre' => 'Garantía de accesorios',
                'categoria' => 'ACCESORIO',
                'meses' => 3,
            ],
        ];

        foreach ($politicas as $politica) {
            DB::table('politicas_garantias')->updateOrInsert(
                ['codigo' => $politica['codigo']],
                [
                    'nombre' => $politica['nombre'],
                    'categoria_producto_id' => $categorias[$politica['categoria']],
                    'producto_id' => null,
                    'duracion_meses' => $politica['meses'],
                    'condiciones' => 'Garantía limitada de hardware según las condiciones vigentes de OneShop.',
                    'exclusiones' => 'Sujeta a las exclusiones establecidas en los términos de garantía de OneShop.',
                    'vigente_desde' => '2026-01-01',
                    'vigente_hasta' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}