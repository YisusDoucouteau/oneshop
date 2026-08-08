<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TecnicoSeeder extends Seeder
{
    public function run(): void
    {
        $categoriaLaptop = DB::table('categorias_productos')
            ->where('codigo', 'LAPTOP')
            ->value('id');

        $plantillaId = DB::table('plantillas_checklist')
            ->where('codigo', 'REVISION_RECEPCION_LAPTOP')
            ->where('version', 1)
            ->value('id');

        if (!$plantillaId) {
            $plantillaId = DB::table('plantillas_checklist')->insertGetId([
                'categoria_producto_id' => $categoriaLaptop,
                'codigo' => 'REVISION_RECEPCION_LAPTOP',
                'nombre' => 'Revisión de recepción de laptop',
                'version' => 1,
                'descripcion' => 'Checklist técnico inicial aplicado a laptops recibidas.',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $items = [
            ['ENCENDIDO', 'Encendido del equipo', 'BOOLEANO', null, null, null, 1],
            ['CARGADOR', 'Funcionamiento del cargador', 'BOOLEANO', null, null, null, 2],

            ['BATERIA', 'Porcentaje de batería', 'NUMERICO', '%', 0, 100, 3],

            ['TECLADO', 'Funcionamiento del teclado', 'BOOLEANO', null, null, null, 4],
            ['CAMARA', 'Funcionamiento de cámara', 'BOOLEANO', null, null, null, 5],
            ['WIFI', 'Conectividad Wi-Fi', 'BOOLEANO', null, null, null, 6],
            ['MICROFONO', 'Funcionamiento de micrófono', 'BOOLEANO', null, null, null, 7],
            ['TOUCHPAD', 'Funcionamiento del touchpad', 'BOOLEANO', null, null, null, 8],
            ['PANTALLA_TACTIL', 'Funcionamiento de pantalla táctil', 'BOOLEANO', null, null, null, 9],

            ['PUERTOS_USB', 'Funcionamiento de puertos USB', 'BOOLEANO', null, null, null, 10],
            ['AUDIO', 'Funcionamiento de audio', 'BOOLEANO', null, null, null, 11],
            ['HDMI', 'Funcionamiento de HDMI', 'BOOLEANO', null, null, null, 12],

            ['SISTEMA_OPERATIVO', 'Funcionamiento del sistema operativo', 'BOOLEANO', null, null, null, 13],
            ['RENDIMIENTO', 'Rendimiento general', 'BOOLEANO', null, null, null, 14],
            ['TEMPERATURA', 'Temperatura y ventilación', 'BOOLEANO', null, null, null, 15],

            ['LIMPIEZA', 'Estado de limpieza', 'BOOLEANO', null, null, null, 16],
        ];

        foreach ($items as [
            $codigo,
            $nombre,
            $tipo,
            $unidad,
            $minimo,
            $maximo,
            $orden
        ]) {
            DB::table('items_checklist')->updateOrInsert(
                [
                    'plantilla_checklist_id' => $plantillaId,
                    'codigo' => $codigo,
                ],
                [
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'tipo_respuesta' => $tipo,
                    'unidad' => $unidad,
                    'valor_minimo' => $minimo,
                    'valor_maximo' => $maximo,
                    'opciones' => null,
                    'requerido' => $codigo !== 'PANTALLA_TACTIL',
                    'orden' => $orden,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}