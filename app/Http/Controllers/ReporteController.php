<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\EstadoEquipo;
use App\Models\User;
use App\Models\Venta;
use App\Models\Proveedor;
use App\Models\CasoGarantia;
use App\Models\Garantia;
use App\Models\Equipo;
use App\Services\ReporteConsultaService;
use App\Services\TrazabilidadEquipoService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReporteConsultaService $reporteConsultaService,
        private readonly TrazabilidadEquipoService $trazabilidadEquipoService
    ) {
    }

    public function index(): View
    {
        return view('reportes.index');
    }

    public function ventas(Request $request): View
    {
        $filtros = $this->validarFiltrosVentas($request);

        /*
         * La vista interactiva usa DataTables. Los filtros de negocio se
         * resuelven en backend y DataTables se encarga de búsqueda rápida,
         * ordenamiento, paginación y adaptación responsive del resultado.
         */
        $ventas = $this->reporteConsultaService
            ->ventasExportacion($filtros);

        $resumen = $this->reporteConsultaService
            ->resumenVentas($filtros);

        $comparativo = $this->reporteConsultaService
            ->compararVentasConPeriodoAnterior($filtros);

        $evolucion = $this->reporteConsultaService
            ->evolucionVentas($filtros);

        $ventasPorVendedor = $this->reporteConsultaService
            ->ventasPorVendedor($filtros)
            ->take(5);

        return view(
            'reportes.ventas.index',
            array_merge(
                $this->catalogos(),
                compact(
                    'ventas',
                    'resumen',
                    'comparativo',
                    'evolucion',
                    'ventasPorVendedor',
                    'filtros'
                )
            )
        );
    }

    public function ventasCsv(Request $request)
    {
        $filtros = $this->validarFiltrosVentas($request);
        $ventas = $this->reporteConsultaService
            ->ventasExportacion($filtros);

        $nombre = 'reporte-ventas-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(
            function () use ($ventas) {
                $salida = fopen('php://output', 'wb');

                echo "\xEF\xBB\xBF";

                fputcsv(
                    $salida,
                    [
                        'Venta',
                        'Fecha',
                        'Cliente',
                        'Vendedor',
                        'Total',
                        'Costo histórico',
                        'Utilidad',
                        'Margen',
                        'Estado',
                    ],
                    ';'
                );

                foreach ($ventas as $venta) {
                    $costo = (float) $venta->detalles->sum(
                        fn ($detalle) =>
                            (float) $detalle->costo_unitario_snapshot
                            * (int) $detalle->cantidad
                    );

                    $total = (float) $venta->total;
                    $utilidad = $total - $costo;
                    $margen = $total > 0
                        ? ($utilidad / $total) * 100
                        : 0;

                    $cliente = $venta->cliente_nombre_snapshot
                        ?: $venta->cliente?->nombre_completo
                        ?: 'Cliente no identificado';

                    fputcsv(
                        $salida,
                        [
                            $this->csvSeguro($venta->numero),
                            $venta->fecha_venta?->format('d/m/Y H:i'),
                            $this->csvSeguro($cliente),
                            $this->csvSeguro($venta->vendedor?->name ?? 'No disponible'),
                            number_format($total, 2, ',', '.'),
                            number_format($costo, 2, ',', '.'),
                            number_format($utilidad, 2, ',', '.'),
                            number_format($margen, 2, ',', '.') . '%',
                            $venta->estado === 'ANULADA' ? 'Anulada' : 'Registrada',
                        ],
                        ';'
                    );
                }

                fclose($salida);
            },
            $nombre,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function ventasPdf(Request $request)
    {
        $filtros = $this->validarFiltrosVentas($request);
        $ventas = $this->reporteConsultaService
            ->ventasExportacion($filtros);
        $resumen = $this->reporteConsultaService
            ->resumenVentas($filtros);

        $filtrosHumanos = $this->filtrosHumanosVentas($filtros);

        return Pdf::loadView(
            'reportes.ventas.pdf',
            compact(
                'ventas',
                'resumen',
                'filtros',
                'filtrosHumanos'
            )
        )
            ->setPaper('a4', 'landscape')
            ->stream(
                'reporte-ventas-'
                . now()->format('Y-m-d-His')
                . '.pdf'
            );
    }

    public function ventasImprimir(Request $request): View
    {
        $filtros = $this->validarFiltrosVentas($request);
        $ventas = $this->reporteConsultaService
            ->ventasExportacion($filtros);
        $resumen = $this->reporteConsultaService
            ->resumenVentas($filtros);

        $filtrosHumanos = $this->filtrosHumanosVentas($filtros);

        return view(
            'reportes.ventas.imprimir',
            compact(
                'ventas',
                'resumen',
                'filtros',
                'filtrosHumanos'
            )
        );
    }

    public function inventario(Request $request): View
    {
        $filtros = $this->validarFiltrosInventario($request);

        $equipos = $this->reporteConsultaService
            ->inventarioExportacion($filtros);

        $resumen = $this->reporteConsultaService
            ->resumenInventarioFormal($filtros);

        return view(
            'reportes.inventario.index',
            array_merge(
                $this->catalogosInventario(),
                compact(
                    'equipos',
                    'resumen',
                    'filtros'
                )
            )
        );
    }

    public function inventarioCsv(Request $request)
    {
        $filtros = $this->validarFiltrosInventario($request);
        $equipos = $this->reporteConsultaService
            ->inventarioExportacion($filtros);

        $nombre = 'reporte-inventario-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(
            function () use ($equipos) {
                $salida = fopen('php://output', 'wb');

                echo "\xEF\xBB\xBF";

                fputcsv(
                    $salida,
                    [
                        'Código',
                        'Serial',
                        'Producto',
                        'Marca',
                        'Modelo',
                        'Almacén',
                        'Estado',
                        'Precio vigente',
                        'Fecha de registro',
                    ],
                    ';'
                );

                foreach ($equipos as $equipo) {
                    fputcsv(
                        $salida,
                        [
                            $this->csvSeguro($equipo->codigo_interno),
                            $this->csvSeguro($equipo->serial_fabricante ?: ''),
                            $this->csvSeguro($equipo->producto?->nombre ?? 'Sin producto'),
                            $this->csvSeguro($equipo->producto?->marca?->nombre ?? 'Sin marca'),
                            $this->csvSeguro($equipo->producto?->modelo ?? ''),
                            $this->csvSeguro($equipo->almacenActual?->nombre ?? 'Sin almacén'),
                            $this->csvSeguro($equipo->estadoActual?->nombre ?? 'Sin estado'),
                            $equipo->precioVigente
                                ? number_format((float) $equipo->precioVigente->precio_publico, 2, ',', '.')
                                : '',
                            $equipo->fecha_registro?->format('d/m/Y H:i'),
                        ],
                        ';'
                    );
                }

                fclose($salida);
            },
            $nombre,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    public function inventarioPdf(Request $request)
    {
        $filtros = $this->validarFiltrosInventario($request);
        $equipos = $this->reporteConsultaService
            ->inventarioExportacion($filtros);
        $resumen = $this->reporteConsultaService
            ->resumenInventarioFormal($filtros);
        $filtrosHumanos = $this->filtrosHumanosInventario($filtros);

        return Pdf::loadView(
            'reportes.inventario.pdf',
            compact(
                'equipos',
                'resumen',
                'filtros',
                'filtrosHumanos'
            )
        )
            ->setPaper('a4', 'landscape')
            ->stream(
                'reporte-inventario-'
                . now()->format('Y-m-d-His')
                . '.pdf'
            );
    }

    public function inventarioImprimir(Request $request): View
    {
        $filtros = $this->validarFiltrosInventario($request);
        $equipos = $this->reporteConsultaService
            ->inventarioExportacion($filtros);
        $resumen = $this->reporteConsultaService
            ->resumenInventarioFormal($filtros);
        $filtrosHumanos = $this->filtrosHumanosInventario($filtros);

        return view(
            'reportes.inventario.imprimir',
            compact(
                'equipos',
                'resumen',
                'filtros',
                'filtrosHumanos'
            )
        );
    }


    public function abastecimientoLogistica(Request $request): View
    {
        $filtros = $this->validarFiltrosAbastecimiento($request);
        $lotes = $this->reporteConsultaService->abastecimientoLogistica($filtros);
        $resumen = $this->reporteConsultaService->resumenAbastecimientoLogistica($filtros);

        return view(
            'reportes.abastecimiento-logistica.index',
            array_merge(
                $this->catalogosAbastecimiento(),
                compact('lotes', 'resumen', 'filtros')
            )
        );
    }

    public function abastecimientoLogisticaCsv(Request $request)
    {
        $filtros = $this->validarFiltrosAbastecimiento($request);
        $lotes = $this->reporteConsultaService->abastecimientoLogistica($filtros);
        $nombre = 'reporte-abastecimiento-logistica-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($lotes) {
            $salida = fopen('php://output', 'wb');
            echo "\xEF\xBB\xBF";

            fputcsv($salida, [
                'Lote', 'Proveedor', 'Fecha compra', 'Esperadas', 'Recibidas CBBA',
                'Preparadas', 'Enviadas', 'Recibidas Oruro', 'Incorporadas',
                'Incidencias activas', 'Costo adquisición BOB', 'Estado del ciclo',
            ], ';');

            foreach ($lotes as $lote) {
                fputcsv($salida, [
                    $this->csvSeguro($lote['codigo']),
                    $this->csvSeguro($lote['proveedor']),
                    $lote['fecha_compra']?->format('d/m/Y') ?? '',
                    $lote['esperadas'],
                    $lote['recibidas_cbba'],
                    $lote['preparadas'],
                    $lote['enviadas'],
                    $lote['recibidas_oruro'],
                    $lote['incorporadas'],
                    $lote['incidencias_activas'],
                    number_format($lote['costo_adquisicion_bob'], 2, ',', '.'),
                    $this->csvSeguro($lote['estado_ciclo']),
                ], ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function abastecimientoLogisticaPdf(Request $request)
    {
        $filtros = $this->validarFiltrosAbastecimiento($request);
        $lotes = $this->reporteConsultaService->abastecimientoLogistica($filtros);
        $resumen = $this->reporteConsultaService->resumenAbastecimientoLogistica($filtros);
        $filtrosHumanos = $this->filtrosHumanosAbastecimiento($filtros);

        return Pdf::loadView(
            'reportes.abastecimiento-logistica.pdf',
            compact('lotes', 'resumen', 'filtros', 'filtrosHumanos')
        )
            ->setPaper('a4', 'landscape')
            ->stream('reporte-abastecimiento-logistica-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function abastecimientoLogisticaImprimir(Request $request): View
    {
        $filtros = $this->validarFiltrosAbastecimiento($request);
        $lotes = $this->reporteConsultaService->abastecimientoLogistica($filtros);
        $resumen = $this->reporteConsultaService->resumenAbastecimientoLogistica($filtros);
        $filtrosHumanos = $this->filtrosHumanosAbastecimiento($filtros);

        return view(
            'reportes.abastecimiento-logistica.imprimir',
            compact('lotes', 'resumen', 'filtros', 'filtrosHumanos')
        );
    }


    public function garantiasPostventa(Request $request): View
    {
        $filtros = $this->validarFiltrosGarantiasPostventa($request);
        $casos = $this->reporteConsultaService->garantiasPostventa($filtros);
        $resumen = $this->reporteConsultaService->resumenGarantiasPostventa($filtros);

        return view(
            'reportes.garantias-postventa.index',
            array_merge(
                $this->catalogosGarantiasPostventa(),
                compact('casos', 'resumen', 'filtros')
            )
        );
    }

    public function garantiasPostventaCsv(Request $request)
    {
        $filtros = $this->validarFiltrosGarantiasPostventa($request);
        $casos = $this->reporteConsultaService->garantiasPostventa($filtros);
        $nombre = 'reporte-garantias-postventa-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($casos) {
            $salida = fopen('php://output', 'wb');
            echo "\xEF\xBB\xBF";

            fputcsv($salida, [
                'Caso',
                'Cliente',
                'Equipo',
                'Producto',
                'Apertura',
                'Cierre',
                'Estado',
                'Motivo',
                'Diagnóstico',
                'Resolución',
                'Días de atención',
                'Equipo de reemplazo',
                'Tipo de ajuste',
                'Estado del ajuste',
                'Diferencia',
            ], ';');

            foreach ($casos as $caso) {
                fputcsv($salida, [
                    $this->csvSeguro($caso['numero']),
                    $this->csvSeguro($caso['cliente']),
                    $this->csvSeguro($caso['equipo']),
                    $this->csvSeguro($caso['producto']),
                    $caso['fecha_apertura']?->format('d/m/Y H:i') ?? '',
                    $caso['fecha_cierre']?->format('d/m/Y H:i') ?? '',
                    $this->csvSeguro($caso['estado_humano']),
                    $this->csvSeguro($caso['motivo']),
                    $this->csvSeguro($caso['diagnostico'] ?? ''),
                    $this->csvSeguro($caso['resolucion'] ?? ''),
                    number_format($caso['dias_atencion'], 1, ',', '.'),
                    $this->csvSeguro($caso['equipo_reemplazo'] ?? ''),
                    $this->csvSeguro(
                        $caso['tipo_ajuste']
                            ? str($caso['tipo_ajuste'])->replace('_', ' ')->title()->toString()
                            : ''
                    ),
                    $this->csvSeguro(
                        $caso['estado_ajuste']
                            ? str($caso['estado_ajuste'])->replace('_', ' ')->title()->toString()
                            : ''
                    ),
                    $caso['diferencia_ajuste'] !== null
                        ? number_format((float) $caso['diferencia_ajuste'], 2, ',', '.')
                        : '',
                ], ';');
            }

            fclose($salida);
        }, $nombre, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function garantiasPostventaPdf(Request $request)
    {
        $filtros = $this->validarFiltrosGarantiasPostventa($request);
        $casos = $this->reporteConsultaService->garantiasPostventa($filtros);
        $resumen = $this->reporteConsultaService->resumenGarantiasPostventa($filtros);
        $filtrosHumanos = $this->filtrosHumanosGarantiasPostventa($filtros);

        return Pdf::loadView(
            'reportes.garantias-postventa.pdf',
            compact('casos', 'resumen', 'filtros', 'filtrosHumanos')
        )
            ->setPaper('a4', 'landscape')
            ->stream('reporte-garantias-postventa-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function garantiasPostventaImprimir(Request $request): View
    {
        $filtros = $this->validarFiltrosGarantiasPostventa($request);
        $casos = $this->reporteConsultaService->garantiasPostventa($filtros);
        $resumen = $this->reporteConsultaService->resumenGarantiasPostventa($filtros);
        $filtrosHumanos = $this->filtrosHumanosGarantiasPostventa($filtros);

        return view(
            'reportes.garantias-postventa.imprimir',
            compact('casos', 'resumen', 'filtros', 'filtrosHumanos')
        );
    }


    public function trazabilidad(Request $request): View
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $filtros['q'] = trim((string) ($filtros['q'] ?? ''));

        $equipos = Equipo::query()
            ->with([
                'producto.marca',
                'almacenActual',
                'estadoActual',
                'incorporacionUnidad.unidadAdquirida.detalleLote.lote.proveedor',
                'detalleLote.lote.proveedor',
            ])
            ->when(
                $filtros['q'] !== '',
                function ($query) use ($filtros) {
                    $busqueda = $filtros['q'];

                    $query->where(function ($subquery) use ($busqueda) {
                        $subquery
                            ->where('codigo_interno', 'like', '%' . $busqueda . '%')
                            ->orWhere('serial_fabricante', 'like', '%' . $busqueda . '%')
                            ->orWhereHas('producto', function ($producto) use ($busqueda) {
                                $producto
                                    ->where('nombre', 'like', '%' . $busqueda . '%')
                                    ->orWhere('modelo', 'like', '%' . $busqueda . '%');
                            });
                    });
                }
            )
            ->orderBy('codigo_interno')
            ->get();

        return view(
            'reportes.trazabilidad.index',
            compact('equipos', 'filtros')
        );
    }

    public function trazabilidadDetalle(Equipo $equipo): View
    {
        return view(
            'reportes.trazabilidad.detalle',
            $this->datosTrazabilidad($equipo)
        );
    }

    public function trazabilidadPdf(Equipo $equipo)
    {
        $datos = $this->datosTrazabilidad($equipo);

        return Pdf::loadView(
            'reportes.trazabilidad.pdf',
            $datos
        )
            ->setPaper('a4', 'portrait')
            ->stream(
                'trazabilidad-'
                . ($equipo->codigo_interno ?: $equipo->id)
                . '-'
                . now()->format('Y-m-d-His')
                . '.pdf'
            );
    }

    public function trazabilidadImprimir(Equipo $equipo): View
    {
        return view(
            'reportes.trazabilidad.imprimir',
            $this->datosTrazabilidad($equipo)
        );
    }

    private function datosTrazabilidad(Equipo $equipo): array
    {
        $equipo->load([
            'producto.marca',
            'producto.categoria',
            'almacenActual',
            'estadoActual',
            'condicionFisica',
            'detalleLote.lote.proveedor',
            'incorporacionUnidad.unidadAdquirida.detalleLote.lote.proveedor',
        ]);

        $eventos = $this->trazabilidadEquipoService->obtener($equipo);

        $unidad = $equipo->incorporacionUnidad?->unidadAdquirida;

        $lote =
            $unidad?->detalleLote?->lote
            ?: $equipo->detalleLote?->lote;

        $proveedor =
            $unidad?->proveedor_compra
            ?: $lote?->proveedor?->nombre;

        $codigoTrazabilidad =
            $unidad?->codigo_trazabilidad;

        return compact(
            'equipo',
            'eventos',
            'unidad',
            'lote',
            'proveedor',
            'codigoTrazabilidad'
        );
    }

    private function validarFiltrosGarantiasPostventa(Request $request): array
    {
        $filtros = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'estado' => ['nullable', 'string', 'max:40'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $filtros['q'] = trim((string) ($filtros['q'] ?? ''));

        return $filtros;
    }

    private function catalogosGarantiasPostventa(): array
    {
        $clientes = Cliente::query()
            ->whereIn(
                'id',
                Venta::query()
                    ->select('cliente_id')
                    ->whereNotNull('cliente_id')
                    ->whereHas('detalles', function ($detalle) {
                        $detalle->whereIn(
                            'id',
                            Garantia::query()
                                ->select('detalle_venta_id')
                                ->whereHas('casosGarantia')
                        );
                    })
            )
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo']);

        $estadosCaso = CasoGarantia::query()
            ->select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        return compact('clientes', 'estadosCaso');
    }

    private function filtrosHumanosGarantiasPostventa(array $filtros): array
    {
        $cliente = !empty($filtros['cliente_id'])
            ? Cliente::query()->find($filtros['cliente_id'])?->nombre_completo
            : null;

        return [
            'periodo' => (!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta']))
                ? (($filtros['fecha_desde'] ?? 'Inicio') . ' a ' . ($filtros['fecha_hasta'] ?? 'Hoy'))
                : 'Todo el histórico',
            'estado' => !empty($filtros['estado'])
                ? str($filtros['estado'])->replace('_', ' ')->title()->toString()
                : 'Todos',
            'cliente' => $cliente ?: 'Todos',
            'busqueda' => $filtros['q'] !== '' ? $filtros['q'] : 'Sin búsqueda adicional',
        ];
    }

    private function validarFiltrosAbastecimiento(Request $request): array
    {
        $filtros = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'proveedor_id' => ['nullable', 'integer', 'exists:proveedores,id'],
            'estado_lote' => ['nullable', 'string', 'max:30'],
            'q' => ['nullable', 'string', 'max:120'],
        ]);

        $filtros['q'] = trim((string) ($filtros['q'] ?? ''));

        return $filtros;
    }

    private function catalogosAbastecimiento(): array
    {
        $proveedores = Proveedor::query()->orderBy('nombre')->get(['id', 'nombre']);

        $estadosLote = \App\Models\Lote::query()
            ->select('estado')
            ->whereNotNull('estado')
            ->distinct()
            ->orderBy('estado')
            ->pluck('estado');

        return compact('proveedores', 'estadosLote');
    }

    private function filtrosHumanosAbastecimiento(array $filtros): array
    {
        $proveedor = !empty($filtros['proveedor_id'])
            ? Proveedor::query()->find($filtros['proveedor_id'])?->nombre
            : null;

        return [
            'periodo' => (!empty($filtros['fecha_desde']) || !empty($filtros['fecha_hasta']))
                ? (($filtros['fecha_desde'] ?? 'Inicio') . ' a ' . ($filtros['fecha_hasta'] ?? 'Hoy'))
                : 'Todo el histórico',
            'proveedor' => $proveedor ?: 'Todos',
            'estado_lote' => !empty($filtros['estado_lote'])
                ? str($filtros['estado_lote'])->replace('_', ' ')->title()->toString()
                : 'Todos',
            'busqueda' => $filtros['q'] !== '' ? $filtros['q'] : 'Sin búsqueda adicional',
        ];
    }

    private function validarFiltrosInventario(Request $request): array
    {
        $filtros = $request->validate([
            'almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'estado_id' => ['nullable', 'integer', 'exists:estados_equipos,id'],
            'q' => ['nullable', 'string', 'max:120'],
            'por_pagina' => ['nullable', 'integer', 'in:10,25,50,100'],
            'orden' => ['nullable', 'in:codigo,serial,producto,almacen,estado,fecha'],
            'direccion' => ['nullable', 'in:asc,desc'],
        ]);

        $filtros['por_pagina'] = (int) ($filtros['por_pagina'] ?? 25);
        $filtros['orden'] = $filtros['orden'] ?? 'fecha';
        $filtros['direccion'] = $filtros['direccion'] ?? 'desc';
        $filtros['q'] = trim((string) ($filtros['q'] ?? ''));

        return $filtros;
    }

    private function catalogosInventario(): array
    {
        $almacenes = Almacen::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'ciudad',
            ]);

        $estados = EstadoEquipo::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id',
                'codigo',
                'nombre',
            ]);

        return compact(
            'almacenes',
            'estados'
        );
    }

    private function filtrosHumanosInventario(array $filtros): array
    {
        $almacen = !empty($filtros['almacen_id'])
            ? Almacen::query()->find($filtros['almacen_id'])?->nombre
            : null;

        $estado = !empty($filtros['estado_id'])
            ? EstadoEquipo::query()->find($filtros['estado_id'])?->nombre
            : null;

        return [
            'almacen' => $almacen ?: 'Todos',
            'estado' => $estado ?: 'Todos',
            'busqueda' => $filtros['q'] !== ''
                ? $filtros['q']
                : 'Sin búsqueda adicional',
        ];
    }

    private function validarFiltrosCentro(Request $request): array
    {
        $filtros = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'vendedor_id' => ['nullable', 'integer', 'exists:users,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'estado_venta' => ['nullable', 'in:REGISTRADA,ANULADA'],
        ]);

        $filtros['fecha_desde'] = $filtros['fecha_desde']
            ?? now()->startOfMonth()->toDateString();

        $filtros['fecha_hasta'] = $filtros['fecha_hasta']
            ?? now()->endOfMonth()->toDateString();

        $filtros['estado_venta'] = $filtros['estado_venta'] ?? 'REGISTRADA';

        return $filtros;
    }

    private function validarFiltrosVentas(Request $request): array
    {
        $filtros = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'vendedor_id' => ['nullable', 'integer', 'exists:users,id'],
            'cliente_id' => ['nullable', 'integer', 'exists:clientes,id'],
            'estado_venta' => ['nullable', 'in:TODAS,REGISTRADA,ANULADA'],
            'q' => ['nullable', 'string', 'max:120'],
            'por_pagina' => ['nullable', 'integer', 'in:10,25,50,100'],
            'orden' => ['nullable', 'in:fecha,numero,total,estado'],
            'direccion' => ['nullable', 'in:asc,desc'],
        ]);

        $filtros['fecha_desde'] = $filtros['fecha_desde']
            ?? now()->startOfMonth()->toDateString();

        $filtros['fecha_hasta'] = $filtros['fecha_hasta']
            ?? now()->endOfMonth()->toDateString();

        $filtros['estado_venta'] = $filtros['estado_venta'] ?? 'REGISTRADA';
        $filtros['por_pagina'] = (int) ($filtros['por_pagina'] ?? 25);
        $filtros['orden'] = $filtros['orden'] ?? 'fecha';
        $filtros['direccion'] = $filtros['direccion'] ?? 'desc';
        $filtros['q'] = trim((string) ($filtros['q'] ?? ''));

        return $filtros;
    }

    private function catalogos(): array
    {
        $vendedores = User::query()
            ->where('activo', true)
            ->whereIn(
                'id',
                Venta::query()
                    ->select('vendedor_id')
                    ->whereNotNull('vendedor_id')
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ]);

        $clientes = Cliente::query()
            ->where('activo', true)
            ->whereHas('ventas')
            ->orderBy('nombre_completo')
            ->get([
                'id',
                'nombre_completo',
            ]);

        $almacenes = Almacen::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'ciudad',
            ]);

        return compact(
            'vendedores',
            'clientes',
            'almacenes'
        );
    }

    private function filtrosHumanosVentas(array $filtros): array
    {
        $vendedor = !empty($filtros['vendedor_id'])
            ? User::query()->find($filtros['vendedor_id'])?->name
            : null;

        $cliente = !empty($filtros['cliente_id'])
            ? Cliente::query()->find($filtros['cliente_id'])?->nombre_completo
            : null;

        $estado = match ($filtros['estado_venta'] ?? 'REGISTRADA') {
            'ANULADA' => 'Anuladas',
            'TODAS' => 'Todas',
            default => 'Registradas',
        };

        return [
            'periodo' => date('d/m/Y', strtotime($filtros['fecha_desde']))
                . ' — '
                . date('d/m/Y', strtotime($filtros['fecha_hasta'])),
            'vendedor' => $vendedor ?: 'Todos',
            'cliente' => $cliente ?: 'Todos',
            'estado' => $estado,
            'busqueda' => $filtros['q'] !== ''
                ? $filtros['q']
                : 'Sin búsqueda adicional',
        ];
    }
    private function csvSeguro(?string $valor): string
    {
        $valor = (string) $valor;

        if ($valor !== '' && in_array($valor[0], ['=', '+', '-', '@'], true)) {
            return "'" . $valor;
        }

        return $valor;
    }

}
