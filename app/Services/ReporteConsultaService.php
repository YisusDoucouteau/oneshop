<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Almacen;
use App\Models\CasoGarantia;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\Lote;
use App\Models\UnidadAdquirida;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReporteConsultaService
{
    public function __construct(
        private readonly IndicadorComercialService $indicadorComercialService
    ) {
    }

    public function centroInformacion(array $filtros = []): array
    {
        return [
            'comercial' => $this->indicadorComercialService
                ->resumenGeneral($filtros),
            'ventasPorVendedor' => $this->indicadorComercialService
                ->ventasPorVendedor($filtros),
            'evolucionVentas' => $this->evolucionVentas($filtros),
            'inventario' => $this->resumenInventario($filtros),
            'reservas' => $this->resumenReservas($filtros),
            'pagos' => $this->resumenPagos($filtros),
            'garantias' => $this->resumenGarantias($filtros),
        ];
    }

    public function ventasDetalle(
        array $filtros = [],
        int $porPagina = 15
    ): LengthAwarePaginator {
        return $this->indicadorComercialService
            ->consultaVentas($filtros)
            ->with([
                'cliente',
                'vendedor',
                'detalles',
            ])
            ->latest('fecha_venta')
            ->paginate($porPagina);
    }


    public function resumenVentas(array $filtros = []): array
    {
        return $this->indicadorComercialService
            ->resumenGeneral($filtros);
    }


    public function compararVentasConPeriodoAnterior(array $filtros = []): array
    {
        $desde = Carbon::parse(
            $filtros['fecha_desde'] ?? now()->startOfMonth()->toDateString()
        )->startOfDay();

        $hasta = Carbon::parse(
            $filtros['fecha_hasta'] ?? now()->endOfMonth()->toDateString()
        )->startOfDay();

        $dias = (int) $desde->diffInDays($hasta) + 1;
        $anteriorHasta = $desde->copy()->subDay();
        $anteriorDesde = $anteriorHasta->copy()->subDays(max(0, $dias - 1));

        $actual = $this->resumenVentas($filtros);

        $filtrosAnterior = array_merge(
            $filtros,
            [
                'fecha_desde' => $anteriorDesde->toDateString(),
                'fecha_hasta' => $anteriorHasta->toDateString(),
            ]
        );

        $anterior = $this->resumenVentas($filtrosAnterior);

        $variacion = function (string $clave) use ($actual, $anterior): ?float {
            $base = (float) ($anterior[$clave] ?? 0);

            if (abs($base) < 0.00001) {
                return null;
            }

            return round(
                (((float) ($actual[$clave] ?? 0) - $base) / abs($base)) * 100,
                1
            );
        };

        return [
            'periodo_anterior' => [
                'desde' => $anteriorDesde->toDateString(),
                'hasta' => $anteriorHasta->toDateString(),
            ],
            'resumen_anterior' => $anterior,
            'variaciones' => [
                'total_vendido' => $variacion('total_vendido'),
                'utilidad_total' => $variacion('utilidad_total'),
                'cantidad_ventas' => $variacion('cantidad_ventas'),
                'ticket_promedio' => $variacion('ticket_promedio'),
            ],
        ];
    }

    public function ventasPorVendedor(array $filtros = []): Collection
    {
        return $this->indicadorComercialService
            ->ventasPorVendedor($filtros);
    }

    public function ventasTabla(
        array $filtros = [],
        int $porPagina = 25
    ): LengthAwarePaginator {
        $query = $this->indicadorComercialService
            ->consultaVentas($filtros)
            ->with([
                'cliente',
                'vendedor',
                'detalles',
            ]);

        $this->ordenarVentas($query, $filtros);

        return $query->paginate($porPagina);
    }

    public function ventasExportacion(array $filtros = []): Collection
    {
        $query = $this->indicadorComercialService
            ->consultaVentas($filtros)
            ->with([
                'cliente',
                'vendedor',
                'detalles',
            ]);

        $this->ordenarVentas($query, $filtros);

        return $query->get();
    }

    private function ordenarVentas(Builder $query, array $filtros): void
    {
        $direccion = ($filtros['direccion'] ?? 'desc') === 'asc'
            ? 'asc'
            : 'desc';

        $columna = match ($filtros['orden'] ?? 'fecha') {
            'numero' => 'numero',
            'total' => 'total',
            'estado' => 'estado',
            default => 'fecha_venta',
        };

        $query->orderBy($columna, $direccion)
            ->orderBy('id', $direccion);
    }

    public function consultaInventario(array $filtros = []): Builder
    {
        return Equipo::query()
            ->where('activo', true)
            ->with([
                'producto.marca',
                'almacenActual',
                'estadoActual',
                'precioVigente',
            ])
            ->when(
                !empty($filtros['almacen_id']),
                fn (Builder $query) => $query->where(
                    'almacen_actual_id',
                    (int) $filtros['almacen_id']
                )
            )
            ->when(
                !empty($filtros['estado_id']),
                fn (Builder $query) => $query->where(
                    'estado_actual_id',
                    (int) $filtros['estado_id']
                )
            )
            ->when(
                !empty($filtros['q']),
                function (Builder $query) use ($filtros) {
                    $busqueda = trim((string) $filtros['q']);

                    $query->where(function (Builder $query) use ($busqueda) {
                        $query
                            ->where('codigo_interno', 'like', "%{$busqueda}%")
                            ->orWhere('serial_fabricante', 'like', "%{$busqueda}%")
                            ->orWhereHas(
                                'producto',
                                function (Builder $producto) use ($busqueda) {
                                    $producto
                                        ->where('codigo', 'like', "%{$busqueda}%")
                                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                                        ->orWhere('modelo', 'like', "%{$busqueda}%")
                                        ->orWhereHas(
                                            'marca',
                                            fn (Builder $marca) => $marca->where(
                                                'nombre',
                                                'like',
                                                "%{$busqueda}%"
                                            )
                                        );
                                }
                            );
                    });
                }
            );
    }

    public function inventarioTabla(
        array $filtros = [],
        int $porPagina = 25
    ): LengthAwarePaginator {
        $query = $this->consultaInventario($filtros);

        $this->ordenarInventario($query, $filtros);

        return $query->paginate($porPagina);
    }

    public function inventarioExportacion(array $filtros = []): Collection
    {
        $query = $this->consultaInventario($filtros);

        $this->ordenarInventario($query, $filtros);

        return $query->get();
    }

    public function resumenInventarioFormal(array $filtros = []): array
    {
        $query = $this->consultaInventario($filtros);

        $total = (clone $query)->count();

        $disponibles = (clone $query)
            ->whereHas(
                'estadoActual',
                fn (Builder $estado) => $estado->where('codigo', 'DISPONIBLE')
            )
            ->count();

        $reservados = (clone $query)
            ->whereHas(
                'estadoActual',
                fn (Builder $estado) => $estado->where('codigo', 'RESERVADO')
            )
            ->count();

        $vendidos = (clone $query)
            ->whereHas(
                'estadoActual',
                fn (Builder $estado) => $estado->where('codigo', 'VENDIDO')
            )
            ->count();

        $conPrecio = (clone $query)
            ->whereHas('precioVigente')
            ->count();

        return [
            'total' => $total,
            'disponibles' => $disponibles,
            'reservados' => $reservados,
            'vendidos' => $vendidos,
            'con_precio' => $conPrecio,
        ];
    }

    private function ordenarInventario(Builder $query, array $filtros): void
    {
        $direccion = ($filtros['direccion'] ?? 'desc') === 'asc'
            ? 'asc'
            : 'desc';

        switch ($filtros['orden'] ?? 'fecha') {
            case 'codigo':
                $query->orderBy('codigo_interno', $direccion);
                break;

            case 'serial':
                $query->orderBy('serial_fabricante', $direccion);
                break;

            case 'producto':
                $query->orderBy(
                    Producto::query()
                        ->select('nombre')
                        ->whereColumn('productos.id', 'equipos.producto_id'),
                    $direccion
                );
                break;

            case 'almacen':
                $query->orderBy(
                    Almacen::query()
                        ->select('nombre')
                        ->whereColumn('almacenes.id', 'equipos.almacen_actual_id'),
                    $direccion
                );
                break;

            case 'estado':
                $query->orderBy(
                    EstadoEquipo::query()
                        ->select('nombre')
                        ->whereColumn('estados_equipos.id', 'equipos.estado_actual_id'),
                    $direccion
                );
                break;

            default:
                $query->orderBy('fecha_registro', $direccion);
                break;
        }

        $query->orderBy('id', $direccion);
    }

    public function evolucionVentas(array $filtros = []): Collection
    {
        return $this->indicadorComercialService
            ->consultaVentas($filtros)
            ->orderBy('fecha_venta')
            ->get([
                'id',
                'fecha_venta',
                'total',
            ])
            ->groupBy(
                fn ($venta) => $venta->fecha_venta->format('Y-m-d')
            )
            ->map(function ($ventas, string $fecha) {
                return [
                    'fecha' => $fecha,
                    'cantidad' => $ventas->count(),
                    'total' => round((float) $ventas->sum('total'), 2),
                ];
            })
            ->values();
    }

    public function resumenInventario(array $filtros = []): array
    {
        $query = Equipo::query()
            ->where('activo', true)
            ->when(
                !empty($filtros['almacen_id']),
                fn (Builder $query) => $query->where(
                    'almacen_actual_id',
                    (int) $filtros['almacen_id']
                )
            );

        $total = (clone $query)->count();

        $porEstado = (clone $query)
            ->with('estadoActual:id,codigo,nombre')
            ->get()
            ->groupBy(
                fn ($equipo) => $equipo->estadoActual?->nombre ?? 'Sin estado'
            )
            ->map(fn ($equipos) => $equipos->count())
            ->sortDesc();

        $porAlmacen = Almacen::query()
            ->where('activo', true)
            ->when(
                !empty($filtros['almacen_id']),
                fn (Builder $query) => $query->whereKey(
                    (int) $filtros['almacen_id']
                )
            )
            ->withCount([
                'equipos as cantidad' => function ($query) {
                    $query->where('activo', true);
                },
            ])
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
                'ciudad',
            ]);

        return [
            'total' => $total,
            'por_estado' => $porEstado,
            'por_almacen' => $porAlmacen,
        ];
    }

    public function resumenReservas(array $filtros = []): array
    {
        $reservas = Reserva::query()
            ->when(
                !empty($filtros['fecha_desde']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_reserva',
                    '>=',
                    $filtros['fecha_desde']
                )
            )
            ->when(
                !empty($filtros['fecha_hasta']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_reserva',
                    '<=',
                    $filtros['fecha_hasta']
                )
            )
            ->get([
                'id',
                'estado',
            ]);

        return [
            'total' => $reservas->count(),
            'activas' => $reservas->where('estado', 'ACTIVA')->count(),
            'por_estado' => $reservas
                ->groupBy('estado')
                ->map(fn ($items) => $items->count())
                ->sortDesc(),
        ];
    }

    public function resumenPagos(array $filtros = []): array
    {
        $pagos = Pago::query()
            ->when(
                !empty($filtros['fecha_desde']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_pago',
                    '>=',
                    $filtros['fecha_desde']
                )
            )
            ->when(
                !empty($filtros['fecha_hasta']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_pago',
                    '<=',
                    $filtros['fecha_hasta']
                )
            )
            ->get([
                'id',
                'estado',
                'monto',
            ]);

        return [
            'total_movimientos' => $pagos->count(),
            'monto_verificado' => round(
                (float) $pagos
                    ->where('estado', 'VERIFICADO')
                    ->sum('monto'),
                2
            ),
            'pendientes' => $pagos->where('estado', 'PENDIENTE')->count(),
            'por_estado' => $pagos
                ->groupBy('estado')
                ->map(fn ($items) => $items->count())
                ->sortDesc(),
        ];
    }

    public function resumenGarantias(array $filtros = []): array
    {
        $casos = CasoGarantia::query()
            ->when(
                !empty($filtros['fecha_desde']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_apertura',
                    '>=',
                    $filtros['fecha_desde']
                )
            )
            ->when(
                !empty($filtros['fecha_hasta']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_apertura',
                    '<=',
                    $filtros['fecha_hasta']
                )
            )
            ->get([
                'id',
                'estado',
            ]);

        return [
            'total' => $casos->count(),
            'abiertos' => $casos
                ->where('estado', '!=', 'CERRADO')
                ->count(),
            'cerrados' => $casos->where('estado', 'CERRADO')->count(),
            'por_estado' => $casos
                ->groupBy('estado')
                ->map(fn ($items) => $items->count())
                ->sortDesc(),
        ];
    }
    public function abastecimientoLogistica(array $filtros = []): Collection
    {
        $query = Lote::query()
            ->with([
                'proveedor:id,nombre',
                'detalles:id,lote_id,cantidad_esperada',
                'unidadesAdquiridas' => function ($query) {
                    $query->select([
                        'unidades_adquiridas.id',
                        'unidades_adquiridas.detalle_lote_id',
                        'unidades_adquiridas.estado',
                        'unidades_adquiridas.precio_compra_bob',
                        'unidades_adquiridas.equipo_id',
                    ])->with([
                        'enviosImportacionUnidades' => function ($query) {
                            $query->with([
                                'envioImportacion:id,estado,fecha_despacho,fecha_recepcion',
                                'incidencias:id,envio_importacion_unidad_id,estado',
                            ]);
                        },
                    ]);
                },
            ])
            ->when(
                !empty($filtros['proveedor_id']),
                fn (Builder $query) => $query->where('proveedor_id', (int) $filtros['proveedor_id'])
            )
            ->when(
                !empty($filtros['estado_lote']),
                fn (Builder $query) => $query->where('estado', $filtros['estado_lote'])
            )
            ->when(
                !empty($filtros['fecha_desde']),
                fn (Builder $query) => $query->whereDate('fecha_compra', '>=', $filtros['fecha_desde'])
            )
            ->when(
                !empty($filtros['fecha_hasta']),
                fn (Builder $query) => $query->whereDate('fecha_compra', '<=', $filtros['fecha_hasta'])
            )
            ->when(
                !empty($filtros['q']),
                function (Builder $query) use ($filtros) {
                    $busqueda = trim((string) $filtros['q']);

                    $query->where(function (Builder $query) use ($busqueda) {
                        $query
                            ->where('codigo', 'like', "%{$busqueda}%")
                            ->orWhere('referencia_compra', 'like', "%{$busqueda}%")
                            ->orWhere('origen', 'like', "%{$busqueda}%")
                            ->orWhereHas(
                                'proveedor',
                                fn (Builder $proveedor) => $proveedor->where('nombre', 'like', "%{$busqueda}%")
                            );
                    });
                }
            )
            ->orderByDesc('fecha_compra')
            ->orderByDesc('id');

        return $query->get()->map(function (Lote $lote) {
            $unidades = $lote->unidadesAdquiridas
                ->where('estado', '!=', UnidadAdquirida::ESTADO_ANULADA)
                ->values();

            $estadosPreparadas = [
                UnidadAdquirida::ESTADO_LISTA_ENVIO,
                UnidadAdquirida::ESTADO_ENVIADA,
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                UnidadAdquirida::ESTADO_INCORPORADA,
            ];

            $estadosEnviadas = [
                UnidadAdquirida::ESTADO_ENVIADA,
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                UnidadAdquirida::ESTADO_INCORPORADA,
            ];

            $estadosRecibidasOruro = [
                UnidadAdquirida::ESTADO_RECIBIDA_ORURO,
                UnidadAdquirida::ESTADO_INCORPORADA,
            ];

            $esperadas = (int) $lote->detalles->sum('cantidad_esperada');
            $recibidasCbba = $unidades->count();
            $preparadas = $unidades->whereIn('estado', $estadosPreparadas)->count();
            $enviadas = $unidades->whereIn('estado', $estadosEnviadas)->count();
            $recibidasOruro = $unidades->whereIn('estado', $estadosRecibidasOruro)->count();
            $incorporadas = $unidades->filter(
                fn ($unidad) => $unidad->estado === UnidadAdquirida::ESTADO_INCORPORADA || $unidad->equipo_id !== null
            )->count();

            $incidenciasActivas = $unidades
                ->flatMap(fn ($unidad) => $unidad->enviosImportacionUnidades)
                ->filter(fn ($asignacion) => $asignacion->envioImportacion?->estado !== 'CANCELADO')
                ->flatMap(fn ($asignacion) => $asignacion->incidencias)
                ->where('estado', '!=', 'RESUELTA')
                ->count();

            return [
                'id' => $lote->id,
                'codigo' => $lote->codigo,
                'proveedor' => $lote->proveedor?->nombre ?? 'Sin proveedor',
                'fecha_compra' => $lote->fecha_compra,
                'estado_lote' => $lote->estado,
                'esperadas' => $esperadas,
                'recibidas_cbba' => $recibidasCbba,
                'preparadas' => $preparadas,
                'enviadas' => $enviadas,
                'recibidas_oruro' => $recibidasOruro,
                'incorporadas' => $incorporadas,
                'pendientes_recepcion' => max(0, $esperadas - $recibidasCbba),
                'pendientes_envio' => $unidades->where('estado', UnidadAdquirida::ESTADO_LISTA_ENVIO)->count(),
                'en_transito' => $unidades->where('estado', UnidadAdquirida::ESTADO_ENVIADA)->count(),
                'pendientes_incorporacion' => $unidades->where('estado', UnidadAdquirida::ESTADO_RECIBIDA_ORURO)->count(),
                'incidencias_activas' => $incidenciasActivas,
                'costo_adquisicion_bob' => round((float) $unidades->sum('precio_compra_bob'), 2),
                'estado_ciclo' => $this->estadoCicloLogistico(
                    $esperadas,
                    $recibidasCbba,
                    $preparadas,
                    $enviadas,
                    $recibidasOruro,
                    $incorporadas,
                    $incidenciasActivas
                ),
            ];
        });
    }

    public function resumenAbastecimientoLogistica(array $filtros = []): array
    {
        $lotes = $this->abastecimientoLogistica($filtros);

        return [
            'lotes' => $lotes->count(),
            'unidades_esperadas' => (int) $lotes->sum('esperadas'),
            'unidades_recibidas_cbba' => (int) $lotes->sum('recibidas_cbba'),
            'pendientes_envio' => (int) $lotes->sum('pendientes_envio'),
            'en_transito' => (int) $lotes->sum('en_transito'),
            'pendientes_incorporacion' => (int) $lotes->sum('pendientes_incorporacion'),
            'incidencias_activas' => (int) $lotes->sum('incidencias_activas'),
            'costo_adquisicion_bob' => round((float) $lotes->sum('costo_adquisicion_bob'), 2),
        ];
    }

    private function estadoCicloLogistico(
        int $esperadas,
        int $recibidasCbba,
        int $preparadas,
        int $enviadas,
        int $recibidasOruro,
        int $incorporadas,
        int $incidenciasActivas
    ): string {
        if ($incidenciasActivas > 0) {
            return 'Con incidencias';
        }

        if ($esperadas > 0 && $recibidasCbba === 0) {
            return 'Pendiente recepción';
        }

        if ($esperadas > 0 && $recibidasCbba < $esperadas) {
            return 'Recepción parcial';
        }

        if ($recibidasCbba > 0 && $preparadas < $recibidasCbba) {
            return 'En preparación';
        }

        if ($preparadas > $enviadas) {
            return 'Listo para envío';
        }

        if ($enviadas > $recibidasOruro) {
            return 'En tránsito';
        }

        if ($recibidasOruro > $incorporadas) {
            return 'Pendiente incorporación';
        }

        if ($recibidasCbba > 0 && $incorporadas >= $recibidasCbba) {
            return 'Completado';
        }

        return 'En proceso';
    }


    public function garantiasPostventa(array $filtros = []): Collection
    {
        $query = CasoGarantia::query()
            ->with([
                'garantia.detalleVenta.venta.cliente',
                'equipoAfectado.producto.marca',
                'cambioEquipo.equipoEntrante.producto',
                'cambioEquipo.movimientosAjuste',
            ])
            ->when(
                !empty($filtros['fecha_desde']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_apertura',
                    '>=',
                    $filtros['fecha_desde']
                )
            )
            ->when(
                !empty($filtros['fecha_hasta']),
                fn (Builder $query) => $query->whereDate(
                    'fecha_apertura',
                    '<=',
                    $filtros['fecha_hasta']
                )
            )
            ->when(
                !empty($filtros['estado']),
                fn (Builder $query) => $query->where(
                    'estado',
                    $filtros['estado']
                )
            )
            ->when(
                !empty($filtros['cliente_id']),
                function (Builder $query) use ($filtros) {
                    $query->whereHas(
                        'garantia.detalleVenta.venta',
                        fn (Builder $venta) => $venta->where(
                            'cliente_id',
                            (int) $filtros['cliente_id']
                        )
                    );
                }
            )
            ->when(
                !empty($filtros['q']),
                function (Builder $query) use ($filtros) {
                    $busqueda = trim((string) $filtros['q']);

                    $query->where(function (Builder $query) use ($busqueda) {
                        $query
                            ->where('numero', 'like', "%{$busqueda}%")
                            ->orWhere('motivo_cliente', 'like', "%{$busqueda}%")
                            ->orWhere('diagnostico_final', 'like', "%{$busqueda}%")
                            ->orWhere('resolucion', 'like', "%{$busqueda}%")
                            ->orWhereHas(
                                'equipoAfectado',
                                function (Builder $equipo) use ($busqueda) {
                                    $equipo
                                        ->where('codigo_interno', 'like', "%{$busqueda}%")
                                        ->orWhere('serial_fabricante', 'like', "%{$busqueda}%");
                                }
                            )
                            ->orWhereHas(
                                'garantia.detalleVenta.venta',
                                function (Builder $venta) use ($busqueda) {
                                    $venta
                                        ->where('cliente_nombre_snapshot', 'like', "%{$busqueda}%")
                                        ->orWhereHas(
                                            'cliente',
                                            fn (Builder $cliente) => $cliente->where(
                                                'nombre_completo',
                                                'like',
                                                "%{$busqueda}%"
                                            )
                                        );
                                }
                            );
                    });
                }
            )
            ->orderByDesc('fecha_apertura')
            ->orderByDesc('id');

        return $query->get()->map(function (CasoGarantia $caso) {
            $venta = $caso->garantia?->detalleVenta?->venta;
            $cambio = $caso->cambioEquipo;

            $cliente = $venta?->cliente_nombre_snapshot
                ?: $venta?->cliente?->nombre_completo
                ?: 'Cliente no identificado';

            $fechaReferencia = $caso->fecha_cierre ?: now();
            $horasAtencion = $caso->fecha_apertura
                ? round($caso->fecha_apertura->diffInMinutes($fechaReferencia) / 60, 1)
                : 0.0;

            $ajusteVerificado = $cambio
                ? round(
                    (float) $cambio->movimientosAjuste
                        ->where('estado', 'VERIFICADO')
                        ->sum('monto'),
                    2
                )
                : 0.0;

            return [
                'id' => $caso->id,
                'numero' => $caso->numero,
                'cliente' => $cliente,
                'equipo' => $caso->equipoAfectado?->codigo_interno ?? 'Sin equipo',
                'serial' => $caso->equipoAfectado?->serial_fabricante,
                'producto' => $caso->equipoAfectado?->producto?->nombre ?? 'Sin producto',
                'fecha_apertura' => $caso->fecha_apertura,
                'fecha_cierre' => $caso->fecha_cierre,
                'estado' => $caso->estado,
                'estado_humano' => str($caso->estado)->replace('_', ' ')->title()->toString(),
                'motivo' => $caso->motivo_cliente,
                'diagnostico' => $caso->diagnostico_final,
                'resolucion' => $caso->resolucion,
                'horas_atencion' => $horasAtencion,
                'dias_atencion' => round($horasAtencion / 24, 1),
                'tiene_reemplazo' => $cambio !== null,
                'equipo_reemplazo' => $cambio?->equipoEntrante?->codigo_interno,
                'tipo_ajuste' => $cambio?->tipo_ajuste,
                'estado_ajuste' => $cambio?->estado_ajuste,
                'diferencia_ajuste' => $cambio?->diferencia_snapshot !== null
                    ? (float) $cambio->diferencia_snapshot
                    : null,
                'moneda_ajuste' => $cambio?->moneda_ajuste,
                'ajuste_verificado' => $ajusteVerificado,
            ];
        });
    }

    public function resumenGarantiasPostventa(array $filtros = []): array
    {
        $casos = $this->garantiasPostventa($filtros);
        $cerrados = $casos->where('estado', 'CERRADO');

        return [
            'total' => $casos->count(),
            'activos' => $casos->where('estado', '!=', 'CERRADO')->count(),
            'cerrados' => $cerrados->count(),
            'con_reemplazo' => $casos->where('tiene_reemplazo', true)->count(),
            'tiempo_promedio_dias' => $cerrados->isNotEmpty()
                ? round((float) $cerrados->avg('dias_atencion'), 1)
                : 0.0,
        ];
    }

}
