<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $origenId = DB::table('estados_equipos')
            ->where('codigo', 'RECIBIDO')
            ->value('id');

        $destinoId = DB::table('estados_equipos')
            ->where('codigo', 'DISPONIBLE')
            ->value('id');

        if (!$origenId || !$destinoId) {
            return;
        }

        DB::table('transiciones_estados_equipos')
            ->updateOrInsert(
                [
                    'estado_origen_id' => $origenId,
                    'estado_destino_id' => $destinoId,
                ],
                [
                    'requiere_autorizacion' => false,
                    'descripcion' => 'Habilitación comercial de equipo recibido para venta.',
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
    }

    public function down(): void
    {
        $origenId = DB::table('estados_equipos')
            ->where('codigo', 'RECIBIDO')
            ->value('id');

        $destinoId = DB::table('estados_equipos')
            ->where('codigo', 'DISPONIBLE')
            ->value('id');

        if (!$origenId || !$destinoId) {
            return;
        }

        DB::table('transiciones_estados_equipos')
            ->where('estado_origen_id', $origenId)
            ->where('estado_destino_id', $destinoId)
            ->delete();
    }
};
