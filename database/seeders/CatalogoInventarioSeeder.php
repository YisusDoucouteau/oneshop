<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EstadoEquipo;
use App\Models\TipoMovimientoInventario;
use App\Models\TransicionEstadoEquipo;

class CatalogoInventarioSeeder extends Seeder
{
    public function run(): void
    {

        $estados = [

            [
                'codigo' => 'DISPONIBLE',
                'nombre' => 'Disponible',
            ],

            [
                'codigo' => 'RESERVADO',
                'nombre' => 'Reservado',
            ],

            [
                'codigo' => 'VENDIDO',
                'nombre' => 'Vendido',
            ],

            [
                'codigo' => 'RECIBIDO',
                'nombre' => 'Recibido',
            ],

            [
                'codigo' => 'PREPARACION',
                'nombre' => 'Preparación',
            ],
[
    'codigo' => 'GARANTIA',
    'nombre' => 'En garantía',
],

        ];


        foreach ($estados as $estado) {

            EstadoEquipo::firstOrCreate(
                [
                    'codigo'=>$estado['codigo']
                ],
                [
                    'nombre'=>$estado['nombre'],
                    'activo'=>true
                ]
            );

        }



        $movimientos = [

            [
                'codigo'=>'RESERVA',
                'nombre'=>'Reserva inventario'
            ],

            [
                'codigo'=>'LIBERACION_RESERVA',
                'nombre'=>'Liberación reserva'
            ],

            [
                'codigo'=>'VENTA',
                'nombre'=>'Venta'
            ],

            [
                'codigo'=>'VENTA_DIRECTA',
                'nombre'=>'Venta directa'
            ],

            [
    'codigo' => 'VENTA_RESERVADA',
    'nombre' => 'Venta proveniente de reserva'
],

[
    'codigo' => 'ANULACION_VENTA',
    'nombre' => 'Anulación de venta'
],

            [
                'codigo'=>'ENTRADA',
                'nombre'=>'Entrada inventario'
            ],

           [
    'codigo' => 'SALIDA',
    'nombre' => 'Salida inventario'
],

[
    'codigo' => 'CAMBIO_GARANTIA',
    'nombre' => 'Salida por cambio de garantía'
],

];


        foreach($movimientos as $movimiento){

            TipoMovimientoInventario::firstOrCreate(
                [
                    'codigo'=>$movimiento['codigo']
                ],
                [
                    'nombre'=>$movimiento['nombre'],
                    'activo'=>true
                ]
            );

        }



        $this->crearTransicion(
            'DISPONIBLE',
            'RESERVADO'
        );


        $this->crearTransicion(
            'RESERVADO',
            'DISPONIBLE'
        );


        $this->crearTransicion(
            'RESERVADO',
            'VENDIDO'
        );


        $this->crearTransicion(
            'DISPONIBLE',
            'VENDIDO'
        );
        $this->crearTransicion(
    'VENDIDO',
    'DISPONIBLE'
);
$this->crearTransicion(
    'VENDIDO',
    'GARANTIA',
    true
);
    }



   private function crearTransicion(
    string $origen,
    string $destino,
    bool $requiereAutorizacion = false
): void
{
    $estadoOrigen =
        EstadoEquipo::where(
            'codigo',
            $origen
        )->first();

    $estadoDestino =
        EstadoEquipo::where(
            'codigo',
            $destino
        )->first();

    if (
        !$estadoOrigen
        || !$estadoDestino
    ) {
        return;
    }

    TransicionEstadoEquipo::updateOrCreate(
        [
            'estado_origen_id' =>
                $estadoOrigen->id,

            'estado_destino_id' =>
                $estadoDestino->id,
        ],
        [
            'requiere_autorizacion' =>
                $requiereAutorizacion,

            'activo' =>
                true,
        ]
    );
}

}
