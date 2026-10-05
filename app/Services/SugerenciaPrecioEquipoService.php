<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleVenta;
use App\Models\Equipo;
use App\Models\PoliticaDescuento;
use Carbon\Carbon;
use InvalidArgumentException;

class SugerenciaPrecioEquipoService
{
    /**
     * Calibración inicial tomada del comportamiento comercial que OneShop
     * venía usando en su hoja de precios histórica.
     *
     * Cuando existan suficientes ventas registradas de la misma categoría,
     * el sistema deja de depender de esta referencia inicial y utiliza la
     * mediana observada del recargo sobre costo en las ventas reales de OneShop.
     */
    private const RECARGO_PUBLICADO_BASE_PORCENTAJE = 60.0;
    private const DESCUENTO_NEGOCIACION_BASE_PORCENTAJE = 10.0;
    private const MARGEN_SUGERIDO_CONSERVADO_PORCENTAJE = 50.0;
    private const COMPARABLES_MINIMOS = 3;
    private const COMPARABLES_MAXIMOS = 30;
    private const REDONDEO_COMERCIAL_BOB = 50.0;

    public function __construct(
        private readonly CostoComercialActualService $costoComercialActualService
    ) {
    }

    public function sugerir(Equipo|int $equipo): array
    {
        if (is_int($equipo)) {
            $equipo = Equipo::query()->find($equipo);
        }

        if (!$equipo) {
            throw new InvalidArgumentException(
                'El equipo indicado no existe.'
            );
        }

        $equipo->loadMissing([
            'producto.categoria',
            'precioVigente',
        ]);

        $costo = $this->costoComercialActualService
            ->calcular($equipo);

        $costoTotal = (float) ($costo['costo_total'] ?? 0);

        if ($costoTotal <= 0) {
            throw new ReglaNegocioException(
                'No existe un costo comercial válido para generar una sugerencia de precio.'
            );
        }

        $diasAntiguedad = $this->diasAntiguedad($equipo);
        $politica = $this->buscarPolitica(
            $equipo,
            $diasAntiguedad
        );

        $comparables = $this->comparables($equipo);
        $recargos = [];
        $descuentos = [];

        foreach ($comparables as $detalle) {
            $costoComparable = (float) $detalle->costo_unitario_snapshot;
            $precioVenta = (float) $detalle->precio_unitario;

            if ($costoComparable <= 0 || $precioVenta <= 0) {
                continue;
            }

            $recargoPorcentaje = (
                ($precioVenta - $costoComparable)
                / $costoComparable
            ) * 100;

            // Protege la referencia contra registros atípicos o dañados.
            if (
                $recargoPorcentaje >= 0
                && $recargoPorcentaje <= 300
            ) {
                $recargos[] = $recargoPorcentaje;
            }

            $precioLista = $detalle->precio_lista_snapshot !== null
                ? (float) $detalle->precio_lista_snapshot
                : null;

            if (
                $precioLista !== null
                && $precioLista > 0
                && $precioVenta <= $precioLista
            ) {
                $descuento = (
                    ($precioLista - $precioVenta)
                    / $precioLista
                ) * 100;

                // Una venta sin rebaja (0 %) sirve para estimar el precio,
                // pero no demuestra cuánto margen de negociación existió.
                // Solo usamos rebajas reales para aprender el corredor comercial.
                if ($descuento > 0 && $descuento <= 50) {
                    $descuentos[] = $descuento;
                }
            }
        }

        $usaHistorial = count($recargos) >= self::COMPARABLES_MINIMOS;

        $recargoObjetivo = $usaHistorial
            ? $this->mediana($recargos)
            : self::RECARGO_PUBLICADO_BASE_PORCENTAJE;

        $usaHistorialRebajas =
            count($descuentos) >= self::COMPARABLES_MINIMOS;

        $descuentoNegociacion = $usaHistorialRebajas
            ? $this->mediana($descuentos)
            : self::DESCUENTO_NEGOCIACION_BASE_PORCENTAJE;

        if (
            $politica
            && $politica->porcentaje_maximo !== null
        ) {
            $descuentoNegociacion = min(
                $descuentoNegociacion,
                (float) $politica->porcentaje_maximo
            );
        }

        $precioSugeridoCrudo = $costoTotal * (
            1 + ($recargoObjetivo / 100)
        );

        if (
            $politica
            && $politica->utilidad_minima_bob !== null
        ) {
            $precioSugeridoCrudo = max(
                $precioSugeridoCrudo,
                $costoTotal
                    + (float) $politica->utilidad_minima_bob
            );
        }

        $precioSugerido = $this->redondearArriba(
            $precioSugeridoCrudo
        );

        $precioMinimoCrudo = $precioSugerido * (
            1 - ($descuentoNegociacion / 100)
        );

        /*
         * Cuando todavía no existe una política comercial específica, no
         * permitimos que el rango sugerido consuma prácticamente todo el
         * margen. El piso conserva al menos la mitad del margen que tendría
         * el precio sugerido. Daniel puede modificar este mínimo al publicar.
         */
        $margenSugerido = max(
            0,
            $precioSugerido - $costoTotal
        );

        $precioMinimoPorMargen = $costoTotal + (
            $margenSugerido
            * (self::MARGEN_SUGERIDO_CONSERVADO_PORCENTAJE / 100)
        );

        $precioMinimoCrudo = max(
            $precioMinimoCrudo,
            $precioMinimoPorMargen,
            $costoTotal
        );

        if (
            $politica
            && $politica->utilidad_minima_bob !== null
        ) {
            $precioMinimoCrudo = max(
                $precioMinimoCrudo,
                $costoTotal
                    + (float) $politica->utilidad_minima_bob
            );
        }

        $precioMinimo = min(
            $precioSugerido,
            $this->redondearArriba($precioMinimoCrudo)
        );

        $margenTotalSugerido = round(
            $precioSugerido - $costoTotal,
            2
        );

        $gananciaParteSugerida = round(
            $margenTotalSugerido / 3,
            2
        );

        $margenTotalMinimo = round(
            $precioMinimo - $costoTotal,
            2
        );

        $gananciaParteMinima = round(
            $margenTotalMinimo / 3,
            2
        );

        $cantidadComparables = count($recargos);
        $cantidadComparablesConRebaja = count($descuentos);

        $confianza = $cantidadComparables >= 10
            ? 'ALTA'
            : (
                $cantidadComparables >= self::COMPARABLES_MINIMOS
                    ? 'MEDIA'
                    : 'INICIAL'
            );

        $fuente = $usaHistorial
            ? 'HISTORIAL_CATEGORIA'
            : 'REFERENCIA_INICIAL_ONESHOP';

        $explicacion = [
            'Costo comercial actual: Bs '
                . number_format($costoTotal, 2, '.', ','),
            'Antigüedad en inventario: '
                . $diasAntiguedad
                . ' día'
                . ($diasAntiguedad === 1 ? '' : 's'),
        ];

        if ($usaHistorial) {
            $explicacion[] = 'Referencia basada en '
                . $cantidadComparables
                . ' venta'
                . ($cantidadComparables === 1 ? '' : 's')
                . ' comparable'
                . ($cantidadComparables === 1 ? '' : 's')
                . ' de la misma categoría.';
        } else {
            $explicacion[] = 'Aún no existen suficientes ventas comparables; se usa la referencia comercial inicial de OneShop.';
        }

        $explicacion[] = 'Rango sugerido de negociación: Bs '
            . number_format($precioMinimo, 2, '.', ',')
            . ' a Bs '
            . number_format($precioSugerido, 2, '.', ',')
            . '.';

        if ($usaHistorialRebajas) {
            $explicacion[] = 'El rango toma como referencia '
                . $cantidadComparablesConRebaja
                . ' rebaja'
                . ($cantidadComparablesConRebaja === 1 ? '' : 's')
                . ' real'
                . ($cantidadComparablesConRebaja === 1 ? '' : 'es')
                . ' comparable'
                . ($cantidadComparablesConRebaja === 1 ? '' : 's')
                . '.';
        } else {
            $explicacion[] = 'Aún no hay suficientes rebajas comparables; el rango usa la referencia inicial de negociación y conserva al menos el '
                . number_format(self::MARGEN_SUGERIDO_CONSERVADO_PORCENTAJE, 0)
                . '% del margen sugerido.';
        }

        if ($politica) {
            $explicacion[] = 'Política aplicable: '
                . $politica->nombre
                . '.';
        } else {
            $explicacion[] = 'No existe una política comercial adicional; el rango se apoya en el costo comercial, el historial disponible y el mínimo operativo que defina administración.';
        }

        return [
            'equipo_id' => $equipo->id,
            'costo_comercial' => round($costoTotal, 2),
            'precio_sugerido' => round($precioSugerido, 2),
            'precio_minimo_sugerido' => round($precioMinimo, 2),
            'margen_total_sugerido' => $margenTotalSugerido,
            'ganancia_parte_sugerida' => $gananciaParteSugerida,
            'margen_total_minimo' => $margenTotalMinimo,
            'ganancia_parte_minima' => $gananciaParteMinima,
            'recargo_objetivo_porcentaje' => round($recargoObjetivo, 2),
            'descuento_negociacion_porcentaje' => round($descuentoNegociacion, 2),
            'comparables_con_rebaja' => $cantidadComparablesConRebaja,
            'origen_rango_negociacion' => $usaHistorialRebajas
                ? 'HISTORIAL_REBAJAS'
                : 'REFERENCIA_BASE',
            'dias_antiguedad' => $diasAntiguedad,
            'categoria' => $equipo->producto?->categoria?->nombre,
            'comparables' => $cantidadComparables,
            'fuente' => $fuente,
            'confianza' => $confianza,
            'politica' => $politica
                ? [
                    'id' => $politica->id,
                    'codigo' => $politica->codigo,
                    'nombre' => $politica->nombre,
                    'porcentaje_maximo' => $politica->porcentaje_maximo !== null
                        ? (float) $politica->porcentaje_maximo
                        : null,
                    'utilidad_minima_bob' => $politica->utilidad_minima_bob !== null
                        ? (float) $politica->utilidad_minima_bob
                        : null,
                ]
                : null,
            'requiere_revision' => $politica === null,
            'explicacion' => $explicacion,
        ];
    }

    private function comparables(Equipo $equipo)
    {
        $categoriaId = $equipo->producto?->categoria_producto_id;

        if ($categoriaId === null) {
            return collect();
        }

        return DetalleVenta::query()
            ->whereNotNull('equipo_id')
            ->where('equipo_id', '<>', $equipo->id)
            ->where('precio_unitario', '>', 0)
            ->where('costo_unitario_snapshot', '>', 0)
            ->whereHas('venta', function ($query) {
                $query->where('estado', 'REGISTRADA');
            })
            ->whereHas('equipo.producto', function ($query) use (
                $categoriaId
            ) {
                $query->where(
                    'categoria_producto_id',
                    $categoriaId
                );
            })
            ->orderByDesc('id')
            ->limit(self::COMPARABLES_MAXIMOS)
            ->get([
                'id',
                'equipo_id',
                'precio_lista_snapshot',
                'precio_unitario',
                'costo_unitario_snapshot',
            ]);
    }

    private function buscarPolitica(
        Equipo $equipo,
        int $diasAntiguedad
    ): ?PoliticaDescuento {
        $categoriaId = $equipo->producto?->categoria_producto_id;
        $fecha = now()->toDateString();

        return PoliticaDescuento::query()
            ->where('activo', true)
            ->where('dias_desde', '<=', $diasAntiguedad)
            ->where(function ($query) use ($diasAntiguedad) {
                $query
                    ->whereNull('dias_hasta')
                    ->orWhere('dias_hasta', '>=', $diasAntiguedad);
            })
            ->whereDate('vigente_desde', '<=', $fecha)
            ->where(function ($query) use ($fecha) {
                $query
                    ->whereNull('vigente_hasta')
                    ->orWhereDate('vigente_hasta', '>=', $fecha);
            })
            ->where(function ($query) use ($categoriaId) {
                $query->whereNull('categoria_producto_id');

                if ($categoriaId !== null) {
                    $query->orWhere(
                        'categoria_producto_id',
                        $categoriaId
                    );
                }
            })
            ->orderByRaw(
                'CASE WHEN categoria_producto_id IS NULL THEN 1 ELSE 0 END'
            )
            ->orderByDesc('dias_desde')
            ->first();
    }

    private function diasAntiguedad(Equipo $equipo): int
    {
        $desde = $equipo->fecha_disponible
            ? Carbon::parse($equipo->fecha_disponible)
            : Carbon::parse($equipo->fecha_registro ?? now());

        return (int) max(
            0,
            $desde
                ->copy()
                ->startOfDay()
                ->diffInDays(now()->startOfDay())
        );
    }

    private function mediana(array $valores): float
    {
        if ($valores === []) {
            return 0.0;
        }

        sort($valores, SORT_NUMERIC);
        $cantidad = count($valores);
        $medio = intdiv($cantidad, 2);

        if ($cantidad % 2 === 1) {
            return (float) $valores[$medio];
        }

        return (
            (float) $valores[$medio - 1]
            + (float) $valores[$medio]
        ) / 2;
    }

    private function redondearArriba(float $valor): float
    {
        return ceil(
            $valor / self::REDONDEO_COMERCIAL_BOB
        ) * self::REDONDEO_COMERCIAL_BOB;
    }
}
