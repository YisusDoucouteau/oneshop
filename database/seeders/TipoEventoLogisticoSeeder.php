<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TipoEventoLogistico;

class TipoEventoLogisticoSeeder extends Seeder
{
    public function run(): void
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

            TipoEventoLogistico::updateOrCreate(
                [
                    'codigo'=>$evento['codigo']
                ],
                [
                    'nombre'=>$evento['nombre'],
                    'orden'=>$evento['orden'],
                    'activo'=>true,
                ]
            );

        }
    }
}