<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([

            SeguridadSeeder::class,

            CatalogoSeeder::class,

            TipoEventoLogisticoSeeder::class,

            ImportacionSeeder::class,

            InventarioSeeder::class,

            TecnicoSeeder::class,

            CondicionFisicaSeeder::class,

            CostosSeeder::class,

            ComercialSeeder::class,

            GarantiaSeeder::class,

            FinanzasSeeder::class,

            ConfiguracionSeeder::class,

            CatalogoInventarioSeeder::class,

        ]);
    }
}