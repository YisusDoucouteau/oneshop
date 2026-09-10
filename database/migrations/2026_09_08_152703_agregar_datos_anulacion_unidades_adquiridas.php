<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::table('unidades_adquiridas', function (Blueprint $table) {

        $table->text('motivo_anulacion')
            ->nullable()
            ->after('observacion_revision');


        $table->foreignId('anulado_por_id')
            ->nullable()
            ->after('motivo_anulacion')
            ->constrained('users');


        $table->timestamp('fecha_anulacion')
            ->nullable()
            ->after('anulado_por_id');

    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
