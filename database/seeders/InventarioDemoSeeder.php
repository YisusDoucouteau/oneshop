<?php

namespace Database\Seeders;

use App\Models\CategoriaProducto;
use App\Models\CondicionFisica;
use App\Models\Equipo;
use App\Models\EspecificacionEquipo;
use App\Models\EstadoEquipo;
use App\Models\Marca;
use App\Models\PrecioEquipo;
use App\Models\Producto;
use App\Models\Almacen;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventarioDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            $categoriaLaptop = CategoriaProducto::where(
                'codigo',
                'LAPTOP'
            )->firstOrFail();

            $almacenOruro = Almacen::where(
                'codigo',
                'ORURO_PRINCIPAL'
            )->firstOrFail();

            $almacenCbba = Almacen::where(
                'codigo',
                'COCHABAMBA'
            )->firstOrFail();

            $condicionA = CondicionFisica::where(
                'codigo',
                'A'
            )->firstOrFail();

            $condicionB = CondicionFisica::where(
                'codigo',
                'B'
            )->firstOrFail();

            $condicionC = CondicionFisica::where(
                'codigo',
                'C'
            )->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Marcas de demostración
            |--------------------------------------------------------------------------
            */

            $dell = Marca::updateOrCreate(
                ['nombre' => 'Dell'],
                [
                    'descripcion' => 'Marca de equipos de computación.',
                    'activo' => true,
                ]
            );

            $hp = Marca::updateOrCreate(
                ['nombre' => 'HP'],
                [
                    'descripcion' => 'Marca de equipos de computación.',
                    'activo' => true,
                ]
            );

            $lenovo = Marca::updateOrCreate(
                ['nombre' => 'Lenovo'],
                [
                    'descripcion' => 'Marca de equipos de computación.',
                    'activo' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Productos
            |--------------------------------------------------------------------------
            */

            $productos = [
                'DEMO-P001' => Producto::updateOrCreate(
                    ['codigo' => 'DEMO-P001'],
                    [
                        'categoria_producto_id' => $categoriaLaptop->id,
                        'marca_id' => $dell->id,
                        'nombre' => 'Dell Latitude',
                        'modelo' => '5420',
                        'descripcion' => 'Producto demostrativo para desarrollo.',
                        'es_serializado' => true,
                        'activo' => true,
                    ]
                ),

                'DEMO-P002' => Producto::updateOrCreate(
                    ['codigo' => 'DEMO-P002'],
                    [
                        'categoria_producto_id' => $categoriaLaptop->id,
                        'marca_id' => $hp->id,
                        'nombre' => 'HP EliteBook',
                        'modelo' => '840 G7',
                        'descripcion' => 'Producto demostrativo para desarrollo.',
                        'es_serializado' => true,
                        'activo' => true,
                    ]
                ),

                'DEMO-P003' => Producto::updateOrCreate(
                    ['codigo' => 'DEMO-P003'],
                    [
                        'categoria_producto_id' => $categoriaLaptop->id,
                        'marca_id' => $lenovo->id,
                        'nombre' => 'Lenovo ThinkPad',
                        'modelo' => 'T14',
                        'descripcion' => 'Producto demostrativo para desarrollo.',
                        'es_serializado' => true,
                        'activo' => true,
                    ]
                ),
            ];

            /*
            |--------------------------------------------------------------------------
            | Equipos
            |--------------------------------------------------------------------------
            */

            $equipos = [
                [
                    'codigo' => 'DEMO-0001',
                    'producto' => $productos['DEMO-P001'],
                    'estado' => 'DISPONIBLE',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionA,
                    'serial' => 'DEMO-DELL-001',
                    'procesador' => 'Intel Core i5-1145G7',
                    'generacion' => '11',
                    'ram' => 16,
                    'disco' => 512,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 91,
                    'precio' => 4200,
                ],

                [
                    'codigo' => 'DEMO-0002',
                    'producto' => $productos['DEMO-P002'],
                    'estado' => 'PENDIENTE_REVISION',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionB,
                    'serial' => 'DEMO-HP-002',
                    'procesador' => 'Intel Core i5-10310U',
                    'generacion' => '10',
                    'ram' => 8,
                    'disco' => 256,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 78,
                    'precio' => null,
                ],

                [
                    'codigo' => 'DEMO-0003',
                    'producto' => $productos['DEMO-P003'],
                    'estado' => 'EN_DIAGNOSTICO',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionB,
                    'serial' => null,
                    'procesador' => 'AMD Ryzen 5 PRO 4650U',
                    'generacion' => null,
                    'ram' => 16,
                    'disco' => 512,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 82,
                    'precio' => null,
                ],

                [
                    'codigo' => 'DEMO-0004',
                    'producto' => $productos['DEMO-P001'],
                    'estado' => 'EN_REPARACION',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionC,
                    'serial' => 'DEMO-DELL-004',
                    'procesador' => 'Intel Core i5-1145G7',
                    'generacion' => '11',
                    'ram' => 8,
                    'disco' => 256,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 62,
                    'precio' => null,
                ],

                [
                    'codigo' => 'DEMO-0005',
                    'producto' => $productos['DEMO-P002'],
                    'estado' => 'RESERVADO',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionA,
                    'serial' => 'DEMO-HP-005',
                    'procesador' => 'Intel Core i5-10310U',
                    'generacion' => '10',
                    'ram' => 16,
                    'disco' => 512,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 88,
                    'precio' => 3950,
                ],

                [
                    'codigo' => 'DEMO-0006',
                    'producto' => $productos['DEMO-P003'],
                    'estado' => 'VENDIDO',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionA,
                    'serial' => 'DEMO-LENOVO-006',
                    'procesador' => 'AMD Ryzen 5 PRO 4650U',
                    'generacion' => null,
                    'ram' => 16,
                    'disco' => 512,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 86,
                    'precio' => 4400,
                ],

                [
                    'codigo' => 'DEMO-0007',
                    'producto' => $productos['DEMO-P001'],
                    'estado' => 'RECIBIDO',
                    'almacen' => $almacenCbba,
                    'condicion' => null,
                    'serial' => null,
                    'procesador' => 'Intel Core i5',
                    'generacion' => '11',
                    'ram' => 8,
                    'disco' => 256,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => null,
                    'precio' => null,
                ],

                [
                    'codigo' => 'DEMO-0008',
                    'producto' => $productos['DEMO-P002'],
                    'estado' => 'DISPONIBLE',
                    'almacen' => $almacenOruro,
                    'condicion' => $condicionB,
                    'serial' => 'DEMO-HP-008',
                    'procesador' => 'Intel Core i7-10610U',
                    'generacion' => '10',
                    'ram' => 16,
                    'disco' => 512,
                    'tipo_disco' => 'SSD',
                    'pantalla' => 14.0,
                    'bateria' => 74,
                    'precio' => 4550,
                ],
            ];

            foreach ($equipos as $datos) {

                $estado = EstadoEquipo::where(
                    'codigo',
                    $datos['estado']
                )->firstOrFail();

                $equipo = Equipo::updateOrCreate(
                    [
                        'codigo_interno' => $datos['codigo'],
                    ],
                    [
                        'producto_id' => $datos['producto']->id,
                        'detalle_lote_id' => null,
                        'almacen_actual_id' => $datos['almacen']->id,
                        'estado_actual_id' => $estado->id,
                        'condicion_fisica_id' => $datos['condicion']?->id,
                        'serial_fabricante' => $datos['serial'],
                        'fecha_registro' => now(),
                        'fecha_disponible' =>
                            $datos['estado'] === 'DISPONIBLE'
                                ? now()
                                : null,
                        'observacion' => 'Registro temporal para pruebas visuales.',
                        'activo' => true,
                    ]
                );

                EspecificacionEquipo::updateOrCreate(
                    [
                        'equipo_id' => $equipo->id,
                    ],
                    [
                        'procesador' => $datos['procesador'],
                        'generacion_procesador' => $datos['generacion'],
                        'ram_gb' => $datos['ram'],
                        'almacenamiento_gb' => $datos['disco'],
                        'tipo_almacenamiento' => $datos['tipo_disco'],
                        'tarjeta_grafica' => null,
                        'pantalla_pulgadas' => $datos['pantalla'],
                        'resolucion' => '1920x1080',
                        'sistema_operativo' => 'Windows 11',
                        'bateria_porcentaje' => $datos['bateria'],
                        'datos_adicionales' => null,
                    ]
                );

                if ($datos['precio'] !== null) {

                    PrecioEquipo::updateOrCreate(
                        [
                            'equipo_id' => $equipo->id,
                            'vigente' => true,
                        ],
                        [
                            'tipo_cambio_id' => null,
                            'costo_total_snapshot' =>
                                $datos['precio'] - 500,
                            'precio_sugerido' =>
                                $datos['precio'],
                            'precio_publico' =>
                                $datos['precio'],
                            'precio_minimo_autorizado' =>
                                $datos['precio'] - 300,
                            'vigente_desde' => now(),
                            'vigente_hasta' => null,
                            'aprobado_por_id' => null,
                            'observacion' =>
                                'Precio temporal para pruebas visuales.',
                        ]
                    );
                }
            }
        });
    }
}