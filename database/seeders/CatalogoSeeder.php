<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $this->cargarCategorias();
        $this->cargarAlmacenes();
        $this->cargarCondicionesFisicas();
        $this->cargarEstadosEquipos();
        $this->cargarTransiciones();
    }

    private function cargarCategorias(): void
    {
        $categorias = [
            [
                'codigo' => 'LAPTOP',
                'nombre' => 'Laptop',
                'descripcion' => 'Computadoras portátiles.',
            ],
            [
                'codigo' => 'PC_ESCRITORIO',
                'nombre' => 'Computadora de escritorio',
                'descripcion' => 'Equipos de computación de escritorio.',
            ],
            [
                'codigo' => 'ALL_IN_ONE',
                'nombre' => 'All in One',
                'descripcion' => 'Computadoras integradas en pantalla.',
            ],
            [
                'codigo' => 'MINI_PC',
                'nombre' => 'Mini PC',
                'descripcion' => 'Computadoras de formato compacto.',
            ],
            [
                'codigo' => 'MONITOR',
                'nombre' => 'Monitor',
                'descripcion' => 'Monitores y pantallas para computadora.',
            ],
            [
                'codigo' => 'COMPONENTE',
                'nombre' => 'Componente',
                'descripcion' => 'Memorias RAM, discos, unidades de almacenamiento y otros componentes.',
            ],
            [
                'codigo' => 'ACCESORIO',
                'nombre' => 'Accesorio',
                'descripcion' => 'Cargadores, cables y otros accesorios.',
            ],
        ];

        foreach ($categorias as $categoria) {
            DB::table('categorias_productos')->updateOrInsert(
                ['codigo' => $categoria['codigo']],
                [
                    'nombre' => $categoria['nombre'],
                    'descripcion' => $categoria['descripcion'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function cargarAlmacenes(): void
    {
        $almacenes = [
            [
                'codigo' => 'ORURO_PRINCIPAL',
                'nombre' => 'Tienda Oruro',
                'ciudad' => 'Oruro',
                'principal' => true,
            ],
            [
                'codigo' => 'COCHABAMBA',
                'nombre' => 'Depósito Cochabamba',
                'ciudad' => 'Cochabamba',
                'principal' => false,
            ],
        ];

        foreach ($almacenes as $almacen) {
            DB::table('almacenes')->updateOrInsert(
                ['codigo' => $almacen['codigo']],
                [
                    'nombre' => $almacen['nombre'],
                    'ciudad' => $almacen['ciudad'],
                    'direccion' => null,
                    'principal' => $almacen['principal'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function cargarCondicionesFisicas(): void
    {
        $condiciones = [
            [
                'codigo' => 'A',
                'nombre' => 'Grado A',
                'descripcion' => 'Equipo en muy buen estado físico, aproximadamente entre 90% y 100%.',
            ],
            [
                'codigo' => 'B',
                'nombre' => 'Grado B',
                'descripcion' => 'Equipo en buen estado físico, aproximadamente entre 70% y 90%.',
            ],
            [
                'codigo' => 'C',
                'nombre' => 'Grado C',
                'descripcion' => 'Equipo con mayor nivel de desgaste físico, aproximadamente entre 50% y 70%.',
            ],
        ];

        foreach ($condiciones as $condicion) {
            DB::table('condiciones_fisicas')->updateOrInsert(
                ['codigo' => $condicion['codigo']],
                [
                    'nombre' => $condicion['nombre'],
                    'descripcion' => $condicion['descripcion'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function cargarEstadosEquipos(): void
    {
        $estados = [
            ['codigo' => 'RECIBIDO', 'nombre' => 'Recibido', 'orden' => 1, 'es_final' => false],
            ['codigo' => 'PENDIENTE_REVISION', 'nombre' => 'Pendiente de revisión', 'orden' => 2, 'es_final' => false],
            ['codigo' => 'EN_DIAGNOSTICO', 'nombre' => 'En diagnóstico', 'orden' => 3, 'es_final' => false],
            ['codigo' => 'EN_REPARACION', 'nombre' => 'En reparación', 'orden' => 4, 'es_final' => false],
            ['codigo' => 'DISPONIBLE', 'nombre' => 'Disponible', 'orden' => 5, 'es_final' => false],
            ['codigo' => 'RESERVADO', 'nombre' => 'Reservado', 'orden' => 6, 'es_final' => false],
            ['codigo' => 'VENDIDO', 'nombre' => 'Vendido', 'orden' => 7, 'es_final' => false],
            ['codigo' => 'EN_GARANTIA', 'nombre' => 'En garantía', 'orden' => 8, 'es_final' => false],
            ['codigo' => 'DEVUELTO', 'nombre' => 'Devuelto', 'orden' => 9, 'es_final' => false],
            ['codigo' => 'DADO_DE_BAJA', 'nombre' => 'Dado de baja', 'orden' => 10, 'es_final' => true],
        ];

        foreach ($estados as $estado) {
            DB::table('estados_equipos')->updateOrInsert(
                ['codigo' => $estado['codigo']],
                [
                    'nombre' => $estado['nombre'],
                    'descripcion' => null,
                    'orden' => $estado['orden'],
                    'es_final' => $estado['es_final'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function cargarTransiciones(): void
    {
        $estados = DB::table('estados_equipos')->pluck('id', 'codigo');

        $transiciones = [
            ['RECIBIDO', 'PENDIENTE_REVISION', false],

            ['PENDIENTE_REVISION', 'EN_DIAGNOSTICO', false],

            ['EN_DIAGNOSTICO', 'EN_REPARACION', false],
            ['EN_DIAGNOSTICO', 'DISPONIBLE', false],
            ['EN_DIAGNOSTICO', 'DADO_DE_BAJA', true],

            ['EN_REPARACION', 'EN_DIAGNOSTICO', false],
            ['EN_REPARACION', 'DISPONIBLE', false],

            ['DISPONIBLE', 'RESERVADO', false],
            ['DISPONIBLE', 'VENDIDO', false],

            ['RESERVADO', 'DISPONIBLE', false],
            ['RESERVADO', 'VENDIDO', false],

            ['VENDIDO', 'EN_GARANTIA', false],

            ['EN_GARANTIA', 'VENDIDO', false],
            ['EN_GARANTIA', 'DEVUELTO', true],

            ['DEVUELTO', 'EN_DIAGNOSTICO', false],
            ['DEVUELTO', 'DADO_DE_BAJA', true],
        ];

        foreach ($transiciones as [$origen, $destino, $requiereAutorizacion]) {
            DB::table('transiciones_estados_equipos')->updateOrInsert(
                [
                    'estado_origen_id' => $estados[$origen],
                    'estado_destino_id' => $estados[$destino],
                ],
                [
                    'requiere_autorizacion' => $requiereAutorizacion,
                    'descripcion' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}