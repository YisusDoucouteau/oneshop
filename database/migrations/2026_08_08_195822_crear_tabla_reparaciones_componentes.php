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
    Schema::create('reparaciones_componentes', function (Blueprint $table) {
        $table->foreignId('reparacion_id')
            ->constrained('reparaciones')
            ->cascadeOnDelete();

        $table->foreignId('asignacion_componente_id')
            ->constrained('asignaciones_componentes')
            ->restrictOnDelete();

        $table->string('observacion', 255)
            ->nullable();

        $table->primary([
            'reparacion_id',
            'asignacion_componente_id',
        ]);

        $table->unique(
            'asignacion_componente_id',
            'uq_asignacion_reparacion'
        );
    });
}

public function down(): void
{
    Schema::dropIfExists('reparaciones_componentes');
}
};
