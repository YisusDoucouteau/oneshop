<?php

namespace App\Services;

use App\Models\Equipo;

class CostoRealEquipoService
{
    /**
     * Calcula el costo real ACTUAL de un equipo.
     *
     * Fuente base, en orden:
     * 1) último HistorialCostoUnidad de la unidad incorporada;
     * 2) costo unitario del detalle de lote;
     * 3) snapshot del precio vigente, solo como fallback legacy;
     * 4) cero si no existe información.
     *
     * Luego suma los costos registrados directamente al equipo.
     */
    public function calcular(Equipo|int $equipo): array
    {
        if (is_int($equipo)) {
            $equipo = Equipo::query()->findOrFail($equipo);
        }

        $equipo->loadMissing([
            'detalleLote',
            'costos',
            'incorporacionUnidad.unidadAdquirida',
            'precios' => function ($query) {
                $query
                    ->where('vigente', true)
                    ->latest('vigente_desde');
            },
        ]);

        $costoBase = 0.0;
        $fuenteBase = 'SIN_INFORMACION';
        $completo = false;
        $advertencias = [];

        $unidad = $equipo
            ->incorporacionUnidad
            ?->unidadAdquirida;

        $historial = $unidad
            ? $unidad
                ->historialCostos()
                ->latest('fecha_calculo')
                ->latest('id')
                ->first()
            : null;

        if ($historial) {
            $costoBase = (float) $historial->costo_total;
            $fuenteBase = 'HISTORIAL_UNIDAD';
            $completo = (bool) $historial->completo;

            if (!$completo) {
                $advertencias[] =
                    'El último costo de la unidad de origen está marcado como incompleto.';
            }
        } elseif (
            $equipo->detalleLote
            && $equipo->detalleLote->costo_unitario_bob !== null
        ) {
            $costoBase =
                (float) $equipo->detalleLote->costo_unitario_bob;

            $fuenteBase = 'DETALLE_LOTE';
            $completo = true;
        } else {
            $precioVigente = $equipo->precios->first();

            if (
                $precioVigente
                && $precioVigente->costo_total_snapshot !== null
            ) {
                $costoBase =
                    (float) $precioVigente->costo_total_snapshot;

                $fuenteBase = 'SNAPSHOT_PRECIO_LEGACY';
                $completo = true;
            } else {
                $advertencias[] =
                    'No existe una fuente de costo base para el equipo.';
            }
        }

        $costosPosteriores = (float) $equipo
            ->costos()
            ->sum('monto_bob');

        return [
            'equipo_id' =>
                $equipo->id,

            'costo_base' =>
                round($costoBase, 2),

            'costos_posteriores' =>
                round($costosPosteriores, 2),

            'costo_total' =>
                round(
                    $costoBase + $costosPosteriores,
                    2
                ),

            'fuente_base' =>
                $fuenteBase,

            'completo' =>
                $completo,

            'advertencias' =>
                $advertencias,
        ];
    }
}
