<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CostosSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['FLETE_INTERNACIONAL', 'Flete internacional', 'LOTE'],
            ['ADUANA', 'Aduana', 'LOTE'],
            ['SEGURO', 'Seguro', 'LOTE'],
            ['CONSOLIDACION', 'Consolidación', 'LOTE'],
            ['TRANSPORTE_INTERNO', 'Transporte interno', 'AMBOS'],
            ['SERVICIO_EXTERNO', 'Servicio técnico externo', 'EQUIPO'],
            ['REPUESTO_EXTERNO', 'Repuesto adquirido externamente', 'EQUIPO'],
            ['OTRO', 'Otro costo', 'AMBOS'],
        ];

        foreach ($tipos as [$codigo, $nombre, $ambito]) {
            DB::table('tipos_costos')->updateOrInsert(
                ['codigo' => $codigo],
                [
                    'nombre' => $nombre,
                    'ambito' => $ambito,
                    'descripcion' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}