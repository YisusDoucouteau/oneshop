<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ImportacionSeeder extends Seeder
{
    public function run(): void
    {
        $this->cargarMonedas();
        $this->cargarTiposEventos();
    }

    private function cargarMonedas(): void
    {
        $monedas = [
            [
                'codigo' => 'BOB',
                'nombre' => 'Boliviano',
                'simbolo' => 'Bs',
            ],
            [
                'codigo' => 'USD',
                'nombre' => 'Dólar estadounidense',
                'simbolo' => '$',
            ],
            [
                'codigo' => 'USDT',
                'nombre' => 'Tether',
                'simbolo' => 'USDT',
            ],
        ];

        foreach ($monedas as $moneda) {
            DB::table('monedas')->updateOrInsert(
                ['codigo' => $moneda['codigo']],
                [
                    'nombre' => $moneda['nombre'],
                    'simbolo' => $moneda['simbolo'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function cargarTiposEventos(): void
    {
        $eventos = [
            [
                'codigo' => 'COMPRA',
                'nombre' => 'Compra',
                'orden' => 1,
            ],
            [
                'codigo' => 'RECEPCION_ORIGEN',
                'nombre' => 'Recepción en origen',
                'orden' => 2,
            ],
            [
                'codigo' => 'DESPACHO_INTERNACIONAL',
                'nombre' => 'Despacho internacional',
                'orden' => 3,
            ],
            [
                'codigo' => 'INGRESO_BOLIVIA',
                'nombre' => 'Ingreso a Bolivia',
                'orden' => 4,
            ],
            [
                'codigo' => 'RECEPCION_COCHABAMBA',
                'nombre' => 'Recepción en Cochabamba',
                'orden' => 5,
            ],
            [
                'codigo' => 'DESPACHO_ORURO',
                'nombre' => 'Despacho a Oruro',
                'orden' => 6,
            ],
            [
                'codigo' => 'RECEPCION_ORURO',
                'nombre' => 'Recepción en Oruro',
                'orden' => 7,
            ],
        ];

        foreach ($eventos as $evento) {
            DB::table('tipos_eventos_logisticos')->updateOrInsert(
                ['codigo' => $evento['codigo']],
                [
                    'nombre' => $evento['nombre'],
                    'descripcion' => null,
                    'orden' => $evento['orden'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}