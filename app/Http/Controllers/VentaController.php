<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Models\MetodoPago;
use App\Models\Venta;
use App\Services\PagoService;
use App\Services\ProcesadorVentaService;
use App\Services\RentabilidadRebajaService;
use App\Services\ValidadorVentaPrecioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class VentaController extends Controller
{
    public function __construct(
        private readonly PagoService $pagoService,
        private readonly ProcesadorVentaService $procesadorVentaService,
        private readonly ValidadorVentaPrecioService $validadorVentaPrecioService,
        private readonly RentabilidadRebajaService $rentabilidadRebajaService
    ) {
    }

    public function index(): View
    {
        $ventas = Venta::query()
            ->with([
                'cliente',
                'vendedor',
                'detalles.equipo.producto',
            ])
            ->latest('fecha_venta')
            ->paginate(15);

        return view(
            'ventas.index',
            compact('ventas')
        );
    }

    /**
     * Formulario operativo de venta directa.
     *
     * Solo ofrece equipos realmente DISPONIBLES y con precio vigente.
     * La validación definitiva sigue ocurriendo en los servicios al guardar.
     */
    public function create(): View
    {
        $clientes = Cliente::query()
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();

        $equipos = Equipo::query()
            ->with([
                'producto.marca',
                'almacenActual',
                'estadoActual',
                'precioVigente',
            ])
            ->where('activo', true)
            ->whereHas(
                'estadoActual',
                fn ($query) =>
                    $query
                        ->where('codigo', 'DISPONIBLE')
                        ->where('activo', true)
            )
            ->whereHas(
                'precios',
                fn ($query) =>
                    $query->where('vigente', true)
            )
            ->orderBy('codigo_interno')
            ->get();

        return view(
            'ventas.create',
            compact(
                'clientes',
                'equipos'
            )
        );
    }

    /**
     * Vista previa comercial sin efectos secundarios.
     *
     * Importante:
     * - vendedorId se envía como null al validador para NO crear
     *   SolicitudDescuento mientras el usuario solamente escribe.
     * - GANANCIA se obtiene del servicio canónico de rentabilidad.
     * - No se exponen costo, TC ni reparto en este endpoint operativo.
     */
    public function evaluarPrecio(
        Request $request,
        Equipo $equipo
    ): JsonResponse {
        $datos = $request->validate([
            'precio' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],
        ]);

        $equipo->loadMissing([
            'estadoActual',
            'precioVigente',
        ]);

        if (
            !$equipo->activo
            ||
            $equipo->estadoActual?->codigo
                !== 'DISPONIBLE'
        ) {
            return response()->json([
                'ok' => false,
                'message' =>
                    'El equipo ya no se encuentra disponible para venta.',
            ], 422);
        }

        try {
            $validacion =
                $this->validadorVentaPrecioService
                    ->validar(
                        equipoId:
                            $equipo->id,

                        precioPropuesto:
                            (float) $datos['precio'],

                        clienteId:
                            isset($datos['cliente_id'])
                                ? (int) $datos['cliente_id']
                                : null,

                        /*
                         * Sin vendedor: evaluar no debe generar
                         * solicitudes de aprobación.
                         */
                        vendedorId:
                            null
                    );

            $rentabilidad =
                $this->rentabilidadRebajaService
                    ->evaluar(
                        $equipo,
                        (float) $datos['precio']
                    );
        } catch (
            ReglaNegocioException
            | InvalidArgumentException $exception
        ) {
            return response()->json([
                'ok' => false,
                'message' =>
                    $exception->getMessage(),
            ], 422);
        }

        $mensaje =
            $validacion['permitido']
                ? 'Precio dentro de la política comercial.'
                : (
                    $validacion['requiere_aprobacion']
                        ? 'Este precio requiere aprobación antes de completar la venta.'
                        : 'El precio no cumple la política comercial vigente.'
                );

        return response()->json([
            'ok' =>
                true,

            'permitido' =>
                (bool) $validacion['permitido'],

            'requiere_aprobacion' =>
                (bool) $validacion['requiere_aprobacion'],

            'estado' =>
                $validacion['estado'],

            'precio_publicado' =>
                (float) $validacion['precio_publicado'],

            'precio_propuesto' =>
                (float) $validacion['precio_propuesto'],

            'descuento' =>
                (float) $validacion['descuento'],

            'porcentaje_descuento' =>
                (float) $validacion['porcentaje_descuento'],

            /*
             * Indicador principal de OneShop.
             * Sale del servicio canónico, no se recalcula aquí.
             */
            'ganancia' =>
                (float) $rentabilidad['ganancia'],

            'message' =>
                $mensaje,
        ]);
    }

    /**
     * Registra la venta usando el orquestador comercial existente.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $datos = $request->validate([
            'cliente_id' => [
                'nullable',
                'integer',
                'exists:clientes,id',
            ],

            'equipos' => [
                'required',
                'array',
                'min:1',
            ],

            'equipos.*.equipo_id' => [
                'required',
                'integer',
                'distinct',
                'exists:equipos,id',
            ],

            'equipos.*.precio' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $equipos =
            collect($datos['equipos'])
                ->map(
                    fn (array $item) => [
                        'equipo_id' =>
                            (int) $item['equipo_id'],

                        'precio' =>
                            (float) $item['precio'],
                    ]
                )
                ->values()
                ->all();

        try {
            $venta =
                $this->procesadorVentaService
                    ->procesarVentaDirecta(
                        vendedorId:
                            (int) $request->user()->id,

                        equipos:
                            $equipos,

                        clienteId:
                            isset($datos['cliente_id'])
                                ? (int) $datos['cliente_id']
                                : null,

                        observacion:
                            $datos['observacion']
                            ?? null
                    );
        } catch (
            ReglaNegocioException
            | InvalidArgumentException $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'venta' =>
                        $exception->getMessage(),
                ]);
        }

        return redirect()
            ->route(
                'ventas.show',
                $venta
            )
            ->with(
                'success',
                'Venta registrada correctamente.'
            );
    }

    public function show(
        Request $request,
        Venta $venta
    ): View {
        $venta->load([
            'cliente',
            'vendedor',
            'reserva',
            'detalles.producto',
            'detalles.equipo.producto',
            'detalles.garantia',
            'pagos.metodoPago',
            'pagos.registradoPor',
        ]);

        $metodosPago = MetodoPago::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $resumenPago = $this->pagoService
            ->obtenerResumenVenta(
                $venta->id
            );

        /*
         * No se recalcula rentabilidad aquí.
         * Solo se agregan snapshots económicos ya congelados.
         */
        $economiaCompleta =
            $venta->detalles->isNotEmpty()
            &&
            $venta->detalles->every(
                fn ($detalle) =>
                    $detalle->ganancia_snapshot
                    !== null
                    &&
                    $detalle->margen_total_snapshot
                    !== null
            );

        $gananciaVenta =
            $economiaCompleta
                ? round(
                    $venta->detalles->sum(
                        fn ($detalle) =>
                            (float)
                            $detalle->ganancia_snapshot
                            *
                            (int)
                            $detalle->cantidad
                    ),
                    2
                )
                : null;

        /*
         * El detalle interno de costo/reparto se reserva para
         * quienes administran precios. El vendedor puede ver
         * GANANCIA, pero no costo, TC ni reparto.
         */
        $puedeVerDetalleEconomico =
            (bool) $request->user()
                ?->tienePermiso(
                    'precios.modificar'
                );

        $resumenEconomicoAdmin = null;

        if (
            $economiaCompleta
            &&
            $puedeVerDetalleEconomico
        ) {
            $resumenEconomicoAdmin = [
                'costo_total' =>
                    round(
                        $venta->detalles->sum(
                            fn ($detalle) =>
                                (float)
                                $detalle
                                    ->costo_unitario_snapshot
                                *
                                (int)
                                $detalle->cantidad
                        ),
                        2
                    ),

                'margen_total' =>
                    round(
                        $venta->detalles->sum(
                            fn ($detalle) =>
                                (float)
                                $detalle
                                    ->margen_total_snapshot
                                *
                                (int)
                                $detalle->cantidad
                        ),
                        2
                    ),

                'hugo' =>
                    round(
                        $venta->detalles->sum(
                            fn ($detalle) =>
                                (float)
                                $detalle->hugo_snapshot
                                *
                                (int)
                                $detalle->cantidad
                        ),
                        2
                    ),

                'daniel' =>
                    round(
                        $venta->detalles->sum(
                            fn ($detalle) =>
                                (float)
                                $detalle->daniel_snapshot
                                *
                                (int)
                                $detalle->cantidad
                        ),
                        2
                    ),

                'tienda' =>
                    round(
                        $venta->detalles->sum(
                            fn ($detalle) =>
                                (float)
                                $detalle->tienda_snapshot
                                *
                                (int)
                                $detalle->cantidad
                        ),
                        2
                    ),
            ];
        }

        return view(
            'ventas.show',
            compact(
                'venta',
                'metodosPago',
                'resumenPago',
                'economiaCompleta',
                'gananciaVenta',
                'puedeVerDetalleEconomico',
                'resumenEconomicoAdmin'
            )
        );
    }
}
