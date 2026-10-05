<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\PoliticaDescuento;
use App\Models\PrecioEquipo;
use App\Models\SolicitudDescuento;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ValidadorVentaPrecioService
{
    public function __construct(
        private readonly CostoComercialActualService $costoComercialActualService
    ) {
    }

    public function validar(
        int $equipoId,
        float $precioPropuesto,
        ?int $clienteId = null,
        ?int $vendedorId = null,
        ?string $motivoSolicitud = null
    ): array {
        if ($precioPropuesto <= 0) {
            throw new InvalidArgumentException(
                'El precio propuesto debe ser mayor a cero.'
            );
        }

        $equipo = Equipo::query()
            ->with('producto')
            ->find($equipoId);

        if (!$equipo) {
            throw new InvalidArgumentException(
                'El equipo no existe.'
            );
        }

        $precio = PrecioEquipo::query()
            ->where('equipo_id', $equipo->id)
            ->where('vigente', true)
            ->orderByDesc('vigente_desde')
            ->first();

        if (!$precio) {
            throw new InvalidArgumentException(
                'El equipo no posee precio vigente.'
            );
        }

        /*
         * La validación comercial debe usar el costo vigente del equipo
         * en este instante, no el costo histórico con el que se registró
         * el precio publicado.
         */
        $costoComercial = $this
            ->costoComercialActualService
            ->calcular($equipo);

        $costo = (float) $costoComercial['costo_total'];

        if ($costo <= 0) {
            throw new InvalidArgumentException(
                'No existe un costo válido para evaluar el precio propuesto.'
            );
        }

        $precioPublicado =
            (float) $precio->precio_publico;

        $descuento =
            $precioPublicado - $precioPropuesto;

        $porcentaje =
            $precioPublicado > 0
                ? ($descuento / $precioPublicado) * 100
                : 0;

        $utilidad =
            $precioPropuesto - $costo;

        $diasAntiguedad = (int) max(
            0,
            ($equipo->fecha_disponible ?? now())
                ->copy()
                ->startOfDay()
                ->diffInDays(now()->startOfDay())
        );

        $categoriaId =
            $equipo->producto?->categoria_producto_id;

        $fecha = now()->toDateString();

        /*
         * Seleccionamos la misma clase de política que usa el simulador:
         * vigente, aplicable por antigüedad y preferentemente específica
         * para la categoría del equipo.
         */
        $politica =
            PoliticaDescuento::query()
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

        $cumplePolitica = true;
        $requiereAprobacion = false;

        if ($politica) {
            if (
                $porcentaje >
                (float) $politica->porcentaje_maximo
            ) {
                $cumplePolitica = false;
            }

            if (
                $utilidad <
                (float) $politica->utilidad_minima_bob
            ) {
                $cumplePolitica = false;
            }

            if (
                !$cumplePolitica &&
                $politica->requiere_autorizacion
            ) {
                $requiereAprobacion = true;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Límite operativo del equipo
        |--------------------------------------------------------------------------
        |
        | El mínimo guardado por administración es la regla inmediata para el
        | vendedor. Debajo de ese valor la venta necesita autorización aunque
        | todavía exista utilidad.
        */

        $precioMinimoAutorizado =
            $precio->precio_minimo_autorizado !== null
                ? (float) $precio->precio_minimo_autorizado
                : null;

        if (
            $precioMinimoAutorizado !== null
            && $precioPropuesto < $precioMinimoAutorizado
            && $utilidad >= 0
        ) {
            $cumplePolitica = false;
            $requiereAprobacion = true;
        }

        /*
         * Sin política adicional ni mínimo operativo no existe una regla
         * suficiente para aprobar automáticamente una negociación especial.
         */
        $requiereRevision =
            $politica === null
            && $precioMinimoAutorizado === null;

        if ($requiereRevision) {
            $cumplePolitica = false;
        }

        /*
         * Una venta con pérdida nunca se autoriza automáticamente ni genera
         * una solicitud ordinaria de descuento.
         */
        $generaPerdida = $utilidad < 0;

        if ($generaPerdida) {
            $cumplePolitica = false;
            $requiereAprobacion = false;
        }

        $solicitud = null;
        $solicitudAprobada = null;

        /*
        |--------------------------------------------------------------------------
        | Autorización previa para este equipo y precio
        |--------------------------------------------------------------------------
        |
        | El equipo es serializado y solo puede venderse una vez. Por eso una
        | autorización aprobada para esta versión de precio + precio solicitado
        | habilita la negociación posterior del mismo vendedor.
        */
        if (
            !$generaPerdida
            && !$cumplePolitica
            && $requiereAprobacion
            && $vendedorId !== null
        ) {
            $consultaAprobada = SolicitudDescuento::query()
                ->where('precio_equipo_id', $precio->id)
                ->where('solicitado_por_id', $vendedorId)
                ->where('precio_solicitado', round($precioPropuesto, 2))
                ->where('estado', 'APROBADA');

            if ($clienteId !== null) {
                $consultaAprobada->where(function ($query) use ($clienteId) {
                    $query
                        ->where('cliente_id', $clienteId)
                        ->orWhereNull('cliente_id');
                });
            }

            $solicitudAprobada =
                $consultaAprobada
                    ->latest('fecha_respuesta')
                    ->first();

            if ($solicitudAprobada) {
                $cumplePolitica = true;
                $requiereAprobacion = false;
            }
        }

        if (
            !$cumplePolitica
            && $requiereAprobacion
            && $vendedorId !== null
        ) {
            $solicitud =
                $this->crearSolicitud(
                    precio: $precio,
                    politica: $politica,
                    clienteId: $clienteId,
                    vendedorId: $vendedorId,
                    precioPropuesto: $precioPropuesto,
                    descuento: $descuento,
                    porcentaje: $porcentaje,
                    costo: $costo,
                    utilidad: $utilidad,
                    motivoSolicitud: $motivoSolicitud
                );
        }

        return [
            'permitido' =>
                $cumplePolitica,

            'precio_publicado' =>
                $precioPublicado,

            'precio_propuesto' =>
                $precioPropuesto,

            'descuento' =>
                round($descuento, 2),

            'porcentaje_descuento' =>
                round($porcentaje, 2),

            /*
             * Este costo ya es comercial/actual.
             * Se conserva la clave "utilidad" por compatibilidad
             * con el módulo existente.
             */
            'costo_actual' =>
                round($costo, 2),

            'utilidad' =>
                round($utilidad, 2),

            'tipo_cambio_id' =>
                $costoComercial['tipo_cambio_id']
                ?? null,

            'tipo_cambio' =>
                $costoComercial['tipo_cambio']
                ?? null,

            'moneda_origen' =>
                $costoComercial['moneda_origen']
                ?? null,

            'monto_origen' =>
                $costoComercial['monto_origen']
                ?? null,

            'fuente_costo' =>
                $costoComercial['fuente']
                ?? null,

            'politica' =>
                $politica,

            'precio_minimo_autorizado' =>
                $precioMinimoAutorizado,

            'requiere_aprobacion' =>
                $requiereAprobacion,

            'solicitud' =>
                $solicitud,

            'solicitud_aprobada' =>
                $solicitudAprobada,

            'estado' =>
                $generaPerdida
                    ? 'NO_RECOMENDADA'
                    : (
                        $solicitudAprobada
                            ? 'AUTORIZADO'
                            : (
                                $cumplePolitica
                                    ? 'APROBADO'
                                    : (
                                        $requiereAprobacion
                                            ? 'REQUIERE_AUTORIZACION'
                                            : 'REQUIERE_REVISION'
                                    )
                            )
                    ),
        ];
    }

    private function crearSolicitud(
        PrecioEquipo $precio,
        ?PoliticaDescuento $politica,
        ?int $clienteId,
        int $vendedorId,
        float $precioPropuesto,
        float $descuento,
        float $porcentaje,
        float $costo,
        float $utilidad,
        ?string $motivoSolicitud = null
    ): SolicitudDescuento {
        return DB::transaction(function () use (
            $precio,
            $politica,
            $clienteId,
            $vendedorId,
            $precioPropuesto,
            $descuento,
            $porcentaje,
            $costo,
            $utilidad,
            $motivoSolicitud
        ) {
            $pendiente = SolicitudDescuento::query()
                ->where('precio_equipo_id', $precio->id)
                ->where('solicitado_por_id', $vendedorId)
                ->where('precio_solicitado', round($precioPropuesto, 2))
                ->where('estado', 'PENDIENTE')
                ->first();

            if ($pendiente) {
                if (
                    $motivoSolicitud
                    && $pendiente->motivo !== $motivoSolicitud
                ) {
                    $pendiente->update([
                        'motivo' => $motivoSolicitud,
                    ]);
                }

                return $pendiente->refresh();
            }

            return SolicitudDescuento::create([
                'precio_equipo_id' =>
                    $precio->id,

                'politica_descuento_id' =>
                    $politica?->id,

                'cliente_id' =>
                    $clienteId,

                'solicitado_por_id' =>
                    $vendedorId,

                'precio_publico_snapshot' =>
                    $precio->precio_publico,

                'precio_solicitado' =>
                    $precioPropuesto,

                'descuento_solicitado' =>
                    $descuento,

                'porcentaje_descuento' =>
                    $porcentaje,

                /*
                 * La solicitud también congela el costo comercial
                 * utilizado para tomar la decisión.
                 */
                'costo_total_snapshot' =>
                    $costo,

                'utilidad_proyectada' =>
                    $utilidad,

                'estado' =>
                    'PENDIENTE',

                'motivo' =>
                    $motivoSolicitud
                    ?: 'Descuento fuera de política comercial.',

                'respondido_por_id' =>
                    null,

                'fecha_respuesta' =>
                    null,

                'motivo_respuesta' =>
                    null,
            ]);
        });
    }
}
