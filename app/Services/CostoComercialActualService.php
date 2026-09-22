<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Equipo;

class CostoComercialActualService
{
    public function __construct(
        private CostoRealEquipoService $costoRealEquipoService,
        private TipoCambioComercialService $tipoCambioComercialService
    ) {
    }

    public function calcular(Equipo|int $equipo): array
    {
        if (is_int($equipo)) {
            $equipo = Equipo::query()->findOrFail($equipo);
        }

        $equipo->loadMissing([
            'costos',
            'incorporacionUnidad.unidadAdquirida.moneda',
            'incorporacionUnidad.unidadAdquirida.detalleLote',
        ]);

        $unidad = $equipo
            ->incorporacionUnidad
            ?->unidadAdquirida;

        if (!$unidad) {
            $historico = $this
                ->costoRealEquipoService
                ->calcular($equipo);

            return [
                'equipo_id' => $equipo->id,
                'costo_total' => (float) $historico['costo_total'],
                'costo_compra_actualizado' => (float) $historico['costo_base'],
                'costos_lote' => 0.0,
                'intervenciones' => 0.0,
                'costos_posteriores' => (float) $historico['costos_posteriores'],
                'moneda_origen' => null,
                'monto_origen' => null,
                'usa_tipo_cambio' => false,
                'tipo_cambio_id' => null,
                'tipo_cambio' => null,
                'fuente' => 'COSTO_HISTORICO',
            ];
        }

        $historial = $unidad
            ->historialCostos()
            ->latest('fecha_calculo')
            ->latest('id')
            ->first();

        if (!$historial) {
            throw new ReglaNegocioException(
                'La unidad no tiene un historial de costo para evaluar su rentabilidad.'
            );
        }

        $moneda = $unidad->moneda;

        $montoOrigen = $unidad->precio_compra !== null
            ? (float) $unidad->precio_compra
            : (
                $unidad->detalleLote?->costo_unitario_origen !== null
                    ? (float) $unidad->detalleLote->costo_unitario_origen
                    : null
            );

        $costosLote = (float) $historial->costos_lote;
        $intervenciones = (float) $historial->intervenciones;
        $costosPosteriores = (float) $equipo->costos()->sum('monto_bob');

        if (!$moneda || $moneda->codigo === 'BOB' || $montoOrigen === null) {
            $costoCompra = $unidad->precio_compra_bob !== null
                ? (float) $unidad->precio_compra_bob
                : (float) $historial->costo_compra;

            return [
                'equipo_id' => $equipo->id,
                'costo_total' => round(
                    $costoCompra + $costosLote + $intervenciones + $costosPosteriores,
                    2
                ),
                'costo_compra_actualizado' => round($costoCompra, 2),
                'costos_lote' => round($costosLote, 2),
                'intervenciones' => round($intervenciones, 2),
                'costos_posteriores' => round($costosPosteriores, 2),
                'moneda_origen' => $moneda?->codigo ?? 'BOB',
                'monto_origen' => $montoOrigen,
                'usa_tipo_cambio' => false,
                'tipo_cambio_id' => null,
                'tipo_cambio' => null,
                'fuente' => 'COSTO_EN_BOB',
            ];
        }

        $tipoCambio = $this
            ->tipoCambioComercialService
            ->vigentePara($moneda);

        if (!$tipoCambio) {
            throw new ReglaNegocioException(
                "No existe tipo de cambio comercial vigente para {$moneda->codigo} → BOB."
            );
        }

        $tc = (float) $tipoCambio->valor;
        $compraActualizada = $montoOrigen * $tc;

        return [
            'equipo_id' => $equipo->id,
            'costo_total' => round(
                $compraActualizada + $costosLote + $intervenciones + $costosPosteriores,
                2
            ),
            'costo_compra_actualizado' => round($compraActualizada, 2),
            'costos_lote' => round($costosLote, 2),
            'intervenciones' => round($intervenciones, 2),
            'costos_posteriores' => round($costosPosteriores, 2),
            'moneda_origen' => $moneda->codigo,
            'monto_origen' => round($montoOrigen, 2),
            'usa_tipo_cambio' => true,
            'tipo_cambio_id' => $tipoCambio->id,
            'tipo_cambio' => round($tc, 6),
            'fuente' => 'TIPO_CAMBIO_COMERCIAL',
        ];
    }
}
