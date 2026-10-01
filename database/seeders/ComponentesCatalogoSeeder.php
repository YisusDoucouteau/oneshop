<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ComponentesCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $categoria =
            CategoriaProducto::query()
                ->updateOrCreate(
                    [
                        'codigo' =>
                            'COMPONENTE',
                    ],
                    [
                        'nombre' =>
                            'Componentes y repuestos',

                        'activo' =>
                            true,
                    ]
                );

        $dell =
            Marca::query()
                ->updateOrCreate(
                    [
                        'nombre' =>
                            'Dell',
                    ],
                    [
                        'descripcion' =>
                            null,

                        'activo' =>
                            true,
                    ]
                );

        $hp =
            Marca::query()
                ->updateOrCreate(
                    [
                        'nombre' =>
                            'HP',
                    ],
                    [
                        'descripcion' =>
                            null,

                        'activo' =>
                            true,
                    ]
                );

        $productos = [
            [
                'codigo' =>
                    'CMP-CARGADOR-DELL-65W-PUNTA-AGUJA-001',

                'marca_id' =>
                    $dell->id,

                'nombre' =>
                    'Cargador Dell 65W - punta aguja',

                'modelo' =>
                    '65W',

                'descripcion' =>
                    'Cargador Dell de 65W con conector tipo punta aguja.',
            ],

            [
                'codigo' =>
                    'CMP-CARGADOR-HP-45W-PUNTA-AZUL-001',

                'marca_id' =>
                    $hp->id,

                'nombre' =>
                    'Cargador HP 45W - punta azul',

                'modelo' =>
                    '45W',

                'descripcion' =>
                    'Cargador HP de 45W con conector de punta azul.',
            ],

            [
                'codigo' =>
                    'CMP-CARGADOR-HP-65W-PUNTA-AZUL-001',

                'marca_id' =>
                    $hp->id,

                'nombre' =>
                    'Cargador HP 65W - punta azul',

                'modelo' =>
                    '65W',

                'descripcion' =>
                    'Cargador HP de 65W con conector de punta azul.',
            ],
        ];

        foreach ($productos as $datos) {
            Producto::query()
                ->updateOrCreate(
                    [
                        'codigo' =>
                            $datos['codigo'],
                    ],
                    [
                        'categoria_producto_id' =>
                            $categoria->id,

                        'marca_id' =>
                            $datos['marca_id'],

                        'nombre' =>
                            $datos['nombre'],

                        'modelo' =>
                            $datos['modelo'],

                        'descripcion' =>
                            $datos['descripcion'],

                        'es_serializado' =>
                            false,

                        'activo' =>
                            true,
                    ]
                );
        }
    }
}
