<?php

namespace App\Http\Controllers;

use App\Models\CasoGarantia;
use App\Models\Equipo;
use App\Models\Garantia;
use App\Models\MetodoPago;
use App\Services\AjusteGarantiaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarantiaController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('buscar', ''));
        $estado = mb_strtolower(trim((string) $request->query('estado', '')));
        $caso = mb_strtolower(trim((string) $request->query('caso', '')));

        $garantias = Garantia::query()
            ->with([
                'detalleVenta.venta.cliente',
                'detalleVenta.equipo.producto.marca',
                'casosGarantia' => function ($query) {
                    $query
                        ->with('recibidoPor')
                        ->orderByDesc('fecha_apertura');
                },
            ])
            ->when(
                $busqueda !== '',
                function ($query) use ($busqueda) {
                    $termino = '%' . $busqueda . '%';

                    $query->where(function ($subquery) use ($termino) {
                        $subquery
                            ->where('numero', 'like', $termino)
                            ->orWhereHas(
                                'detalleVenta.equipo',
                                function ($equipoQuery) use ($termino) {
                                    $equipoQuery
                                        ->where('codigo_interno', 'like', $termino)
                                        ->orWhere('serial_fabricante', 'like', $termino);
                                }
                            )
                            ->orWhereHas(
                                'detalleVenta.venta',
                                function ($ventaQuery) use ($termino) {
                                    $ventaQuery
                                        ->where('numero', 'like', $termino)
                                        ->orWhere('cliente_nombre_snapshot', 'like', $termino)
                                        ->orWhere('cliente_telefono_snapshot', 'like', $termino)
                                        ->orWhereHas(
                                            'cliente',
                                            function ($clienteQuery) use ($termino) {
                                                $clienteQuery
                                                    ->where('nombre_completo', 'like', $termino)
                                                    ->orWhere('telefono', 'like', $termino);
                                            }
                                        );
                                }
                            )
                            ->orWhereHas(
                                'casosGarantia',
                                function ($casoQuery) use ($termino) {
                                    $casoQuery
                                        ->where('numero', 'like', $termino)
                                        ->orWhere('motivo_cliente', 'like', $termino);
                                }
                            );
                    });
                }
            )
            ->when(
                $estado === 'vigente',
                fn ($query) => $query
                    ->where('estado', 'VIGENTE')
                    ->where('fecha_fin', '>=', now())
            )
            ->when(
                $estado === 'vencida',
                fn ($query) => $query
                    ->where('estado', 'VIGENTE')
                    ->where('fecha_fin', '<', now())
            )
            ->when(
                $estado === 'anulada',
                fn ($query) => $query->where('estado', 'ANULADA')
            )
            ->when(
                $caso === 'activo',
                fn ($query) => $query->whereHas(
                    'casosGarantia',
                    fn ($casoQuery) => $casoQuery->whereIn(
                        'estado',
                        [
                            'ABIERTO',
                            'DIAGNOSTICADO',
                            'EN_PROCESO',
                        ]
                    )
                )
            )
            ->when(
                $caso === 'cerrado',
                fn ($query) => $query->whereHas(
                    'casosGarantia',
                    fn ($casoQuery) => $casoQuery->where('estado', 'CERRADO')
                )
            )
            ->when(
                $caso === 'sin_caso',
                fn ($query) => $query->whereDoesntHave('casosGarantia')
            )
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $resumen = [
            'total' => Garantia::query()->count(),

            'vigentes' => Garantia::query()
                ->where('estado', 'VIGENTE')
                ->where('fecha_fin', '>=', now())
                ->count(),

            'vencidas' => Garantia::query()
                ->where('estado', 'VIGENTE')
                ->where('fecha_fin', '<', now())
                ->count(),

            'casos_activos' => CasoGarantia::query()
                ->whereIn(
                    'estado',
                    [
                        'ABIERTO',
                        'DIAGNOSTICADO',
                        'EN_PROCESO',
                    ]
                )
                ->count(),
        ];

        return view(
            'garantias.index',
            compact(
                'garantias',
                'resumen',
                'busqueda',
                'estado',
                'caso'
            )
        );
    }

    public function show(
        Garantia $garantia,
        AjusteGarantiaService $ajusteGarantiaService
    ): View {
        return $this->renderDetalle(
            garantia: $garantia,
            ajusteGarantiaService: $ajusteGarantiaService
        );
    }

    public function showCase(
        CasoGarantia $caso,
        AjusteGarantiaService $ajusteGarantiaService
    ): View {
        $caso->load('garantia');

        return $this->renderDetalle(
            garantia: $caso->garantia,
            ajusteGarantiaService: $ajusteGarantiaService,
            casoEnfocado: $caso
        );
    }

    private function renderDetalle(
        Garantia $garantia,
        AjusteGarantiaService $ajusteGarantiaService,
        ?CasoGarantia $casoEnfocado = null
    ): View {
        $garantia->load([
            'politicaGarantia',
            'detalleVenta.venta.cliente',
            'detalleVenta.venta.vendedor',
            'detalleVenta.equipo',
        ]);

        $equipo = $garantia->detalleVenta?->equipo;

        abort_if(!$equipo, 404);

        $equipo->load([
            'producto.marca',
            'producto.categoria',
            'almacenActual',
            'estadoActual',
            'condicionFisica',
            'precioVigente',

            'detallesVentas.garantia',
            'detallesVentas.venta.cliente',

            'casosGarantia.garantia',
            'casosGarantia.recibidoPor',
            'casosGarantia.cerradoPor',
            'casosGarantia.intervenciones.usuario',
            'casosGarantia.cambioEquipo.equipoSaliente',
            'casosGarantia.cambioEquipo.equipoEntrante',
            'casosGarantia.cambioEquipo.autorizadoPor',
            'casosGarantia.cambioEquipo.movimientosAjuste.metodoPago',
            'casosGarantia.cambioEquipo.movimientosAjuste.registradoPor',
            'casosGarantia.cambioEquipo.movimientosAjuste.verificadoPor',
        ]);

        if (
            $casoEnfocado
            && (int) $casoEnfocado->garantia_id !== (int) $garantia->id
        ) {
            abort(404);
        }

        $equiposReemplazo = collect();

        if (
            auth()->user()?->tienePermiso(
                'garantias.autorizar_cambio'
            )
        ) {
            $equiposReemplazo = Equipo::query()
                ->with([
                    'almacenActual',
                    'producto.marca',
                    'producto.categoria',
                    'estadoActual',
                    'precioVigente',
                ])
                ->where('id', '<>', $equipo->id)
                ->where('activo', true)
                ->whereNotNull('almacen_actual_id')
                ->whereHas(
                    'estadoActual',
                    fn ($query) => $query->where('codigo', 'DISPONIBLE')
                )
                ->whereDoesntHave(
                    'detallesReservas.reserva',
                    fn ($query) => $query->where('estado', 'ACTIVA')
                )
                ->whereHas(
                    'precioVigente',
                    fn ($query) => $query->where(
                        'precio_publico',
                        '>',
                        0
                    )
                )
                ->whereExists(
                    function ($query) {
                        $query
                            ->selectRaw('1')
                            ->from('existencias_productos')
                            ->whereColumn(
                                'existencias_productos.producto_id',
                                'equipos.producto_id'
                            )
                            ->whereColumn(
                                'existencias_productos.almacen_id',
                                'equipos.almacen_actual_id'
                            )
                            ->where(
                                'existencias_productos.cantidad_disponible',
                                '>',
                                0
                            );
                    }
                )
                ->orderBy('codigo_interno')
                ->get()
                ->sortBy(function ($candidato) use ($equipo) {
                    $mismoProducto =
                        (int) $candidato->producto_id
                        === (int) $equipo->producto_id;

                    $mismaCategoria =
                        (int) ($candidato->producto?->categoria_producto_id ?? 0)
                        ===
                        (int) ($equipo->producto?->categoria_producto_id ?? 0);

                    return sprintf(
                        '%d-%d-%s',
                        $mismoProducto ? 0 : 1,
                        $mismaCategoria ? 0 : 1,
                        $candidato->codigo_interno
                    );
                })
                ->values();
        }

        $metodosPagoAjuste = collect();

        if (
            auth()->user()?->tienePermiso(
                'garantias.ajustes.registrar'
            )
        ) {
            $metodosPagoAjuste = MetodoPago::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();
        }

        $resumenesAjusteGarantia = collect();

        foreach ($equipo->casosGarantia as $caso) {
            if (!$caso->cambioEquipo) {
                continue;
            }

            $resumenesAjusteGarantia->put(
                $caso->cambioEquipo->id,
                $ajusteGarantiaService->obtenerResumen(
                    $caso->cambioEquipo->id
                )
            );
        }

        return view(
            'garantias.show',
            compact(
                'garantia',
                'equipo',
                'equiposReemplazo',
                'metodosPagoAjuste',
                'resumenesAjusteGarantia',
                'casoEnfocado'
            )
        );
    }
}
