<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades_adquiridas', function (Blueprint $table) {
            $table->unsignedTinyInteger('bateria_porcentaje')
                ->nullable()
                ->after('sistema_operativo');

            $table->json('checklist_tecnico')
                ->nullable()
                ->after('bateria_porcentaje');

            $table->string('resultado_revision', 30)
                ->nullable()
                ->after('checklist_tecnico');
        });
    }

    public function down(): void
    {
        Schema::table('unidades_adquiridas', function (Blueprint $table) {
            $table->dropColumn([
                'bateria_porcentaje',
                'checklist_tecnico',
                'resultado_revision',
            ]);
        });
    }
};
