<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CondicionFisica;

class CondicionFisicaSeeder extends Seeder
{
    public function run(): void
    {
        $condiciones = [

            [
                'codigo' => 'NUEVO',
                'nombre' => 'Nuevo',
                'descripcion' => 'Equipo nuevo sin uso previo',
            ],

            [
                'codigo' => 'EXCELENTE',
                'nombre' => 'Excelente',
                'descripcion' => 'Equipo en excelente estado',
            ],

            [
                'codigo' => 'BUENO',
                'nombre' => 'Bueno',
                'descripcion' => 'Equipo en buen estado',
            ],

            [
                'codigo' => 'REGULAR',
                'nombre' => 'Regular',
                'descripcion' => 'Equipo con detalles',
            ],

            [
                'codigo' => 'DEFECTUOSO',
                'nombre' => 'Defectuoso',
                'descripcion' => 'Equipo con fallas',
            ],

        ];


        foreach ($condiciones as $condicion) {

            CondicionFisica::updateOrCreate(
                [
                    'codigo' => $condicion['codigo'],
                ],
                [
                    'nombre' => $condicion['nombre'],
                    'descripcion' => $condicion['descripcion'],
                    'activo' => true,
                ]
            );

        }
    }
}