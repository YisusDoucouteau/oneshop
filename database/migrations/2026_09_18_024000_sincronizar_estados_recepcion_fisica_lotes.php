<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Solo saneamos lotes que ya participan del flujo nuevo basado en
         * UnidadAdquirida. Así no reinterpretamos lotes históricos que fueron
         * gestionados exclusivamente con el contador legacy cantidad_recibida.
         */
        $loteIds = DB::table('unidades_adquiridas')
            ->join(
                'detalles_lotes',
                'detalles_lotes.id',
                '=',
                'unidades_adquiridas.detalle_lote_id'
            )
            ->whereNotNull('unidades_adquiridas.detalle_lote_id')
            ->distinct()
            ->pluck('detalles_lotes.lote_id');

        foreach ($loteIds as $loteId) {
            $lote = DB::table('lotes')
                ->where('id', $loteId)
                ->first();

            if (
                ! $lote
                || in_array($lote->estado, ['CERRADO', 'CANCELADO'], true)
            ) {
                continue;
            }

            $esperadas = (int) DB::table('detalles_lotes')
                ->where('lote_id', $loteId)
                ->sum('cantidad_esperada');

            $recibidasFisicamente = DB::table('unidades_adquiridas')
                ->join(
                    'detalles_lotes',
                    'detalles_lotes.id',
                    '=',
                    'unidades_adquiridas.detalle_lote_id'
                )
                ->where('detalles_lotes.lote_id', $loteId)
                ->where('unidades_adquiridas.estado', '!=', 'ANULADA')
                ->count();

            $estado = match (true) {
                $recibidasFisicamente === 0 => 'ABIERTO',
                $esperadas > 0 && $recibidasFisicamente >= $esperadas => 'RECIBIDO',
                default => 'RECEPCION_PARCIAL',
            };

            if ($lote->estado !== $estado) {
                DB::table('lotes')
                    ->where('id', $loteId)
                    ->update([
                        'estado' => $estado,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Saneamiento de datos: el estado previo no puede reconstruirse con certeza.
    }
};
