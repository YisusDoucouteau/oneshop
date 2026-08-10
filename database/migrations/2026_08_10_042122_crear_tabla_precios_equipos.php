<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('precios_equipos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('equipo_id')
                ->constrained('equipos')
                ->restrictOnDelete();

            $table->foreignId('tipo_cambio_id')
                ->nullable()
                ->constrained('tipos_cambio')
                ->restrictOnDelete();

            $table->decimal('costo_total_snapshot', 14, 2)
                ->nullable();

            $table->decimal('precio_sugerido', 14, 2)
                ->nullable();

            $table->decimal('precio_publico', 14, 2);

            $table->decimal('precio_minimo_autorizado', 14, 2)
                ->nullable();

            $table->dateTime('vigente_desde');

            $table->dateTime('vigente_hasta')
                ->nullable();

            $table->boolean('vigente')
                ->default(true);

            $table->foreignId('aprobado_por_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observacion')
                ->nullable();

            $table->timestamps();

            $table->index([
                'equipo_id',
                'vigente',
            ], 'idx_precio_equipo_vigente');
        });

        DB::statement(
            'ALTER TABLE precios_equipos
             ADD CONSTRAINT chk_precio_publico
             CHECK (precio_publico > 0)'
        );

        DB::statement(
            'ALTER TABLE precios_equipos
             ADD CONSTRAINT chk_precio_minimo
             CHECK (
                 precio_minimo_autorizado IS NULL
                 OR precio_minimo_autorizado <= precio_publico
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('precios_equipos');
    }
};