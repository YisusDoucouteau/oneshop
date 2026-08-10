<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanzasSeeder extends Seeder
{
    public function run(): void
    {
        $conceptos = [
            ['CAPITAL_EQUIPO', 'Capital del equipo', false],
            ['UTILIDAD', 'Utilidad distribuida', true],
            ['COMISION', 'Comisión', true],
            ['ACCESORIO', 'Accesorio', false],
            ['AJUSTE', 'Ajuste', false],
        ];

        foreach ($conceptos as [$codigo, $nombre, $requiereBeneficiario]) {
            DB::table('conceptos_depositos')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'descripcion' => null,
                    'requiere_beneficiario' => $requiereBeneficiario,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $beneficiarios = [
            ['DANIEL', 'Daniel', 'SOCIO'],
            ['SERGIO', 'Sergio', 'SOCIO'],
            ['TIENDA', 'Tienda', 'EMPRESA'],
        ];

        foreach ($beneficiarios as [$codigo, $nombre, $tipo]) {
            DB::table('beneficiarios_distribucion')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'tipo' => $tipo,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $categoriasGastos = [
            ['ALQUILER', 'Alquiler'],
            ['SERVICIOS', 'Servicios'],
            ['TRANSPORTE', 'Transporte'],
            ['MANTENIMIENTO', 'Mantenimiento'],
            ['PUBLICIDAD', 'Publicidad'],
            ['INSUMOS', 'Insumos'],
            ['OTRO', 'Otro'],
        ];

        foreach ($categoriasGastos as [$codigo, $nombre]) {
            DB::table('categorias_gastos')->updateOrInsert(
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