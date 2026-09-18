<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\UnidadAdquirida;
use App\Services\LoteService;
use App\Services\TipoCambioService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ImportacionController extends Controller
{
    public function index(Request $request): View
    {

        $buscar = trim(
            (string) $request->get('buscar', '')
        );

        $estado = trim(
            (string) $request->get('estado', '')
        );

        $lotes = Lote::query()

            ->with('proveedor')

            ->withSum(
                'detalles as cantidad_esperada_total',
                'cantidad_esperada'
            )

            ->withCount([
                'unidadesAdquiridas as cantidad_recibida_fisica_total' =>
                    fn ($query) => $query->where(
                        'unidades_adquiridas.estado',
                        '!=',
                        UnidadAdquirida::ESTADO_ANULADA
                    ),
            ])

            ->when(
                $buscar !== '',
                function ($query) use ($buscar) {

                    $query->where(function ($sub) use ($buscar) {

                        $sub
                            ->where(
                                'codigo',
                                'like',
                                "%{$buscar}%"
                            )

                            ->orWhere(
                                'referencia_compra',
                                'like',
                                "%{$buscar}%"
                            )

                            ->orWhere(
                                'origen',
                                'like',
                                "%{$buscar}%"
                            );
                    });
                }
            )

            ->when(
                $estado !== '',
                fn ($q) => $q->where(
                    'estado',
                    $estado
                )
            )

            ->latest('id')

            ->paginate(15)

            ->withQueryString();

        $resumen = [

            'total' => Lote::count(),

            'abiertos' => Lote::where(
                'estado',
                'ABIERTO'
            )->count(),

            'parciales' => Lote::where(
                'estado',
                'RECEPCION_PARCIAL'
            )->count(),

            'recibidos' => Lote::where(
                'estado',
                'RECIBIDO'
            )->count(),

        ];

        return view(
            'importaciones.index',
            compact(
                'lotes',
                'resumen',
                'buscar',
                'estado'
            )
        );
    }

    public function create(): View
    {

        $proveedores =
            Proveedor::where(
                'activo',
                true
            )
                ->orderBy('nombre')
                ->get();

        $prefijoCodigo =
            'IMP-'.now()->format('Y').'-';

        $correlativo = 1;

        do {
            $codigoSugerido =
                $prefijoCodigo
                .str_pad(
                    (string) $correlativo,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

            $correlativo++;
        } while (
            Lote::query()
                ->where('codigo', $codigoSugerido)
                ->exists()
        );

        return view(
            'importaciones.create',
            compact(
                'proveedores',
                'codigoSugerido'
            )
        );
    }

    public function store(
        Request $request,
        LoteService $loteService
    ): RedirectResponse {

        try {

            $lote =
                $loteService->crearLote(
                    $request->user()->id,
                    $request->all()
                );

            return redirect()

                ->route(
                    'importaciones.show',
                    $lote
                )

                ->with(
                    'success',
                    'Lote creado correctamente.'
                );
        } catch (ReglaNegocioException $e) {

            return back()

                ->withInput()

                ->withErrors([

                    'registro' => $e->getMessage(),

                ]);
        }
    }

    public function show(
        Lote $lote,
        TipoCambioService $tipoCambioService
    ): View {

        $lote->load([

            'proveedor',

            'costos.tipoCosto',
            'costos.moneda',
            'costos.tipoCambio',

            'detalles.producto.marca',
            'detalles.producto.categoria',

            'detalles.moneda',
            'detalles.tipoCambioCompra',
            'detalles.especificacionEsperada',
            'detalles.componentesEsperados',

            'detalles.unidadesAdquiridas.producto.marca',
            'detalles.unidadesAdquiridas.moneda',
            'detalles.unidadesAdquiridas.tipoCambio',
            'detalles.unidadesAdquiridas.almacenActual',

        ]);

        $productos =
            Producto::with([
                'marca',
                'categoria',
            ])
                ->where(
                    'activo',
                    true
                )
                ->orderBy('nombre')
                ->orderBy('modelo')
                ->get();

        $categorias =
            CategoriaProducto::where(
                'activo',
                true
            )
                ->orderBy('nombre')
                ->get();

        $marcas =
            Marca::where(
                'activo',
                true
            )
                ->orderBy('nombre')
                ->get();

        $monedas =
            Moneda::where(
                'activo',
                true
            )
                ->whereIn(
                    'codigo',
                    [
                        'BOB',
                        'USD',
                        'USDT',
                    ]
                )
                ->orderBy('codigo')
                ->get();

        $cantidadEsperada =
            $lote->detalles
                ->sum('cantidad_esperada');

        $cantidadRecibida =
            $lote->detalles
                ->sum(
                    fn ($detalle) => $detalle
                        ->unidadesAdquiridas
                        ->where(
                            'estado',
                            '!=',
                            UnidadAdquirida::ESTADO_ANULADA
                        )
                        ->count()
                );

        $referenciaUsdBob = null;

        try {

            $referenciaUsdBob =
                $tipoCambioService
                    ->obtenerReferenciaUsdBob();
        } catch (ReglaNegocioException $e) {

            $referenciaUsdBob = null;
        }

        return view(

            'importaciones.show',

            compact(

                'lote',

                'productos',

                'categorias',

                'marcas',

                'monedas',

                'cantidadEsperada',

                'cantidadRecibida',

                'referenciaUsdBob'

            )

        );
    }

    public function storeDetalle(
        Request $request,
        Lote $lote,
        LoteService $loteService
    ): JsonResponse {

        try {

            $detalle = $loteService->agregarDetalle(

                $request->user()->id,

                $lote->id,

                $request->all()

            );

            $detalle->load(
                'producto.marca'
            );

            return response()->json([

                'ok' => true,

                'detalle' => [

                    'id' => $detalle->id,

                    'producto' => trim(
                        ($detalle->producto->marca?->nombre ?? '')
                            .' '.
                            $detalle->producto->nombre
                            .' '.
                            ($detalle->producto->modelo ?? '')
                    ),

                    'cantidad_esperada' => $detalle->cantidad_esperada,

                    'cantidad_recibida' => $detalle->cantidad_recibida,

                ],

            ]);
        } catch (\Throwable $e) {

            return response()->json([

                'ok' => false,

                'message' => $e->getMessage(),

            ], 422);
        }
    }

    /**
     * Registro rápido de equipos recibidos por Hugo.
     *
     * Aquí NO se crea inventario.
     * Solo UnidadAdquirida.
     */
    public function storeUnidad(
        Request $request,
        Lote $lote,
        UnidadAdquiridaService $service
    ): RedirectResponse {

        $datos = $request->validate([

            'detalle_lote_id' => [
                'required',
                'exists:detalles_lotes,id',
            ],

            'cantidad' => [
                'required',
                'integer',
                'in:1',
            ],

            // HARDWARE

            'procesador' => [
                'nullable',
                'string',
                'max:150',
            ],

            'generacion_procesador' => [
                'nullable',
                'string',
                'max:80',
            ],

            'ram_gb' => [
                'nullable',
                'integer',
                'min:0',
                'max:65535',
            ],

            'almacenamiento_gb' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'tipo_almacenamiento' => [
                'nullable',
                'string',
                'max:50',
            ],

            'tarjeta_grafica' => [
                'nullable',
                'string',
                'max:150',
            ],

            'serial_fabricante' => [
                'nullable',
                'string',
                'max:150',
            ],

            'grado_recibido' => [
                'required',
                'in:A,B,C',
            ],

            'tiene_cargador' => [
                'required',
                'boolean',
            ],

            // DATOS EXTRA

            'sistema_operativo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'resolucion' => [
                'nullable',
                'string',
                'max:50',
            ],

            'pantalla_pulgadas' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'servicio_requerido' => [
                'nullable',
                'string',
                'max:255',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],

        ]);

        $detalle = $lote->detalles()
            ->with('especificacionEsperada')
            ->find($datos['detalle_lote_id']);

        if (! $detalle) {
            abort(404);
        }

        $especificacionEsperada = $detalle->especificacionEsperada;

        foreach ([
            'procesador',
            'generacion_procesador',
            'ram_gb',
            'almacenamiento_gb',
            'tipo_almacenamiento',
            'tarjeta_grafica',
            'sistema_operativo',
            'resolucion',
            'pantalla_pulgadas',
        ] as $campo) {
            if (($datos[$campo] ?? null) === null) {
                $datos[$campo] = $especificacionEsperada?->{$campo};
            }
        }

        if (blank($datos['procesador'] ?? null)) {
            return back()
                ->withErrors([
                    'procesador' => 'Debe registrar o confirmar el procesador del equipo recibido.',
                ])
                ->withInput();
        }

        if (empty($datos['cantidad'])) {

            return back()
                ->withErrors([
                    'cantidad' => 'Cantidad inválida',
                ]);
        }

        try {
            $service->registrarLlegadaCochabamba(
                $request->user()->id,
                $datos['detalle_lote_id'],
                $datos['cantidad'],
                null,
                $datos['observacion'] ?? null,
                [
                    'procesador' => $datos['procesador'] ?? null,
                    'generacion_procesador' => $datos['generacion_procesador'] ?? null,
                    'ram_gb' => $datos['ram_gb'] ?? null,
                    'almacenamiento_gb' => $datos['almacenamiento_gb'] ?? null,
                    'tipo_almacenamiento' => $datos['tipo_almacenamiento'] ?? null,
                    'tarjeta_grafica' => $datos['tarjeta_grafica'] ?? null,
                    'serial_fabricante' => $datos['serial_fabricante'] ?? null,
                    'grado_recibido' => $datos['grado_recibido'],
                    'tiene_cargador' => $datos['tiene_cargador'],
                    'sistema_operativo' => $datos['sistema_operativo'] ?? null,
                    'resolucion' => $datos['resolucion'] ?? null,
                    'pantalla_pulgadas' => $datos['pantalla_pulgadas'] ?? null,
                    'servicio_requerido' => $datos['servicio_requerido'] ?? null,
                ]
            );
        } catch (ReglaNegocioException $e) {
            return back()
                ->withInput()
                ->withErrors([
                    'recepcion' => $e->getMessage(),
                ]);
        }

        return redirect()
            ->route('importaciones.show', $lote)
            ->with(
                'success',
                'Equipo recibido registrado correctamente.'
            );
    }

    public function anularUnidad(
        Request $request,
        UnidadAdquirida $unidad,
        UnidadAdquiridaService $service
    ) {

        $datos = $request->validate([

            'motivo_anulacion' => [
                'required',
                'string',
                'max:255',
            ],

        ]);

        if (
            $unidad->estado === UnidadAdquirida::ESTADO_ANULADA
        ) {

            return back()
                ->withErrors([
                    'unidad' => 'El equipo ya está anulado.',
                ]);

        }

        if (
            ! in_array(
                $unidad->estado,
                [
                    UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                    UnidadAdquirida::ESTADO_EN_REVISION,
                    UnidadAdquirida::ESTADO_EN_PREPARACION,
                ],
                true
            )
        ) {
            return back()->withErrors([
                'unidad' => 'La recepción de esta unidad ya fue cerrada. No puede anularse desde este módulo.',
            ]);
        }

        $unidad->update([

            'estado' => UnidadAdquirida::ESTADO_ANULADA,

            'motivo_anulacion' => $datos['motivo_anulacion'],

            'anulado_por_id' => auth()->id(),

            'fecha_anulacion' => now(),

        ]);

        $loteId = $unidad->detalleLote?->lote_id;

        if ($loteId) {
            $service->sincronizarEstadoRecepcionLote(
                $loteId
            );
        }

        return back()
            ->with(

                'success',

                'Equipo anulado correctamente.'

            );

    }
}
