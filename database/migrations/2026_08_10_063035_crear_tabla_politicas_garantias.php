<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('politicas_garantias', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 80)
                ->unique();

            $table->string('nombre', 150);

            $table->foreignId('categoria_producto_id')
                ->nullable()
                ->constrained('categorias_productos')
                ->restrictOnDelete();

            $table->foreignId('producto_id')
                ->nullable()
                ->constrained('productos')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('duracion_meses');

            $table->text('condiciones');

            $table->text('exclusiones')
                ->nullable();

            $table->date('vigente_desde');

            $table->date('vigente_hasta')
                ->nullable();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

            $table->index([
                'categoria_producto_id',
                'activo',
            ]);

            $table->index([
                'producto_id',
                'activo',
            ]);
        });

        DB::statement(
            'ALTER TABLE politicas_garantias
             ADD CONSTRAINT chk_duracion_garantia
             CHECK (duracion_meses > 0)'
        );

        DB::statement(
            'ALTER TABLE politicas_garantias
             ADD CONSTRAINT chk_vigencia_garantia
             CHECK (
                vigente_hasta IS NULL
                OR vigente_hasta >= vigente_desde
             )'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_garantias');
    }
};