<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politicas_descuentos', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 60)
                ->unique();

            $table->string('nombre', 120);

            $table->foreignId('categoria_producto_id')
                ->nullable()
                ->constrained('categorias_productos')
                ->restrictOnDelete();

            $table->string('base_antiguedad', 30)
                ->default('FECHA_DISPONIBLE');

            $table->unsignedInteger('dias_desde');

            $table->unsignedInteger('dias_hasta')
                ->nullable();

            $table->decimal('porcentaje_maximo', 5, 2)
                ->nullable();

            $table->decimal('utilidad_minima_bob', 14, 2)
                ->nullable();

            $table->boolean('permite_precio_costo')
                ->default(false);

            $table->boolean('requiere_autorizacion')
                ->default(false);

            $table->date('vigente_desde');

            $table->date('vigente_hasta')
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();
        });

        DB::statement(
            'ALTER TABLE politicas_descuentos
             ADD CONSTRAINT chk_politica_dias
             CHECK (
                 dias_hasta IS NULL
                 OR dias_hasta >= dias_desde
             )'
        );

        DB::statement(
            'ALTER TABLE politicas_descuentos
             ADD CONSTRAINT chk_politica_porcentaje
             CHECK (
                 porcentaje_maximo IS NULL
                 OR porcentaje_maximo BETWEEN 0 AND 100
             )'
        );

        DB::statement(
            'ALTER TABLE politicas_descuentos
             ADD CONSTRAINT chk_politica_utilidad
             CHECK (
                 utilidad_minima_bob IS NULL
                 OR utilidad_minima_bob >= 0
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_descuentos');
    }
};