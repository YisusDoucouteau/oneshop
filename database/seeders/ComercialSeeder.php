<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComercialSeeder extends Seeder
{
    public function run(): void
    {
        $metodos = [
            [
                'codigo' => 'EFECTIVO',
                'nombre' => 'Efectivo',
                'requiere_verificacion' => false,
            ],
            [
                'codigo' => 'QR',
                'nombre' => 'Pago por QR',
                'requiere_verificacion' => true,
            ],
            [
                'codigo' => 'TRANSFERENCIA',
                'nombre' => 'Transferencia bancaria',
                'requiere_verificacion' => true,
            ],
        ];

        foreach ($metodos as $metodo) {
            DB::table('metodos_pago')->updateOrInsert(
                ['codigo' => $metodo['codigo']],
                [
                    'nombre' => $metodo['nombre'],
                    'requiere_verificacion' => $metodo['requiere_verificacion'],
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}