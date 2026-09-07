<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $this->cargarPoliticaDistribucion();
        $this->cargarParametros();
    }


    private function cargarPoliticaDistribucion(): void
    {
        $politicaId = DB::table('politicas_distribucion')
            ->where('codigo', 'DISTRIBUCION_GENERAL')
            ->value('id');


        if (!$politicaId) {

            $politicaId = DB::table('politicas_distribucion')
                ->insertGetId([
                    'codigo' => 'DISTRIBUCION_GENERAL',
                    'nombre' => 'Distribución general de utilidad',
                    'vigente_desde' => '2026-01-01',
                    'vigente_hasta' => null,
                    'activo' => true,
                    'descripcion' => 'Distribución vigente de la utilidad operativa de las ventas.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }


        $beneficiarios = DB::table('beneficiarios_distribucion')
            ->pluck('id', 'codigo');


        foreach (['DANIEL', 'SERGIO', 'TIENDA'] as $codigo) {

            DB::table('detalles_politicas_distribucion')
                ->updateOrInsert(
                    [
                        'politica_distribucion_id' => $politicaId,
                        'beneficiario_id' => $beneficiarios[$codigo],
                    ],
                    [
                        'partes' => 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
        }
    }



    private function cargarParametros(): void
    {

        $parametros = [

            [
                'codigo' => 'RESERVA_DIAS_MAXIMOS_ESTANDAR',
                'nombre' => 'Días máximos estándar de una reserva',
                'modulo' => 'RESERVAS',
                'tipo' => 'ENTERO',
                'valor' => '7',
                'descripcion' => 'Duración máxima estándar antes de requerir una prórroga autorizada.',
            ],


            [
                'codigo' => 'RESERVA_DIAS_RECORDATORIO',
                'nombre' => 'Días de anticipación para recordar vencimiento',
                'modulo' => 'RESERVAS',
                'tipo' => 'ENTERO',
                'valor' => '1',
                'descripcion' => 'Anticipación utilizada para alertar sobre una reserva próxima a vencer.',
            ],


            [
                'codigo' => 'DEPOSITO_DIAS_HABILES_ESPERA',
                'nombre' => 'Días hábiles de espera antes del depósito',
                'modulo' => 'FINANZAS',
                'tipo' => 'ENTERO',
                'valor' => '1',
                'descripcion' => 'Periodo operativo inicial antes de habilitar el depósito de una venta.',
            ],


            /*
            |--------------------------------------------------------------------------
            | Inventario
            |--------------------------------------------------------------------------
            */


            [
                'codigo' => 'INVENTARIO_PREFIJO',
                'nombre' => 'Prefijo del código interno de inventario',
                'modulo' => 'INVENTARIO',
                'tipo' => 'TEXTO',
                'valor' => 'OS-',
                'descripcion' => 'Prefijo utilizado para generar automáticamente los códigos internos de inventario de los equipos.',
            ],


            [
                'codigo' => 'INVENTARIO_ULTIMO_CORRELATIVO',
                'nombre' => 'Último correlativo del inventario',
                'modulo' => 'INVENTARIO',
                'tipo' => 'ENTERO',
                'valor' => '1620',
                'descripcion' => 'Último número utilizado para la generación automática del código interno de inventario.',
            ],

        ];



        foreach ($parametros as $parametro) {

            DB::table('parametros_sistema')
                ->updateOrInsert(
                    [
                        'codigo' => $parametro['codigo'],
                    ],
                    [
                        'nombre' => $parametro['nombre'],
                        'modulo' => $parametro['modulo'],
                        'tipo_dato' => $parametro['tipo'],
                        'valor' => $parametro['valor'],
                        'descripcion' => $parametro['descripcion'],
                        'editable' => true,
                        'activo' => true,
                        'modificado_por_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
        }
    }
}