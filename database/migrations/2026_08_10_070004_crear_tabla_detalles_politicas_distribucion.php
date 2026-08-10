<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalles_politicas_distribucion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('politica_distribucion_id')
                ->constrained('politicas_distribucion')
                ->cascadeOnDelete();

            $table->foreignId('beneficiario_id')
                ->constrained('beneficiarios_distribucion')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('partes')
                ->default(1);

            $table->timestamps();

            $table->unique(
                ['politica_distribucion_id', 'beneficiario_id'],
                'uq_politica_beneficiario'
            );
        });

        DB::statement(
            'ALTER TABLE detalles_politicas_distribucion
             ADD CONSTRAINT chk_partes_distribucion
             CHECK (partes > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('detalles_politicas_distribucion');
    }
};