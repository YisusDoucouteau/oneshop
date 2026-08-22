<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\LoteService;
use App\Services\TipoCambioService;
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
            ->withSum(
                'detalles as cantidad_recibida_total',
                'cantidad_recibida'
            )
            ->when(
                $buscar !== '',
                function ($query) use ($buscar) {
                    $query->where(
                        function ($subquery) use ($buscar) {
                            $subquery
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
                                )
                                ->orWhereHas(
                                    'proveedor',
                                    fn ($proveedor) =>
                                        $proveedor->where(
                                            'nombre',
                                            'like',
                                            "%{$buscar}%"
                                        )
                                );
                        }
                    );
                }
            )
            ->when(
                $estado !== '',
                fn ($query) =>
                    $query->where(
                        'estado',
                        $estado
                    )
            )
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $resumen = [
            'total' =>
                Lote::count(),

            'abiertos' =>
                Lote::where(
                    'estado',
                    'ABIERTO'
                )->count(),

            'parciales' =>
                Lote::where(
                    'estado',
                    'RECEPCION_PARCIAL'
                )->count(),

            'recibidos' =>
                Lote::where(
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
        $proveedores = Proveedor::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view(
            'importaciones.create',
            compact('proveedores')
        );
    }

    public function store(
        Request $request,
        LoteService $loteService
    ): RedirectResponse {
        try {

            $lote = $loteService->crearLote(
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

        } catch (ReglaNegocioException $exception) {

            return back()
                ->withInput()
                ->withErrors([
                    'registro' =>
                        $exception->getMessage(),
                ]);
        }
    }

    public function show(
        Lote $lote,
        TipoCambioService $tipoCambioService
    ): View {
        $lote->load([
            'proveedor',

            'detalles.producto.marca',
            'detalles.producto.categoria',
            'detalles.moneda',
            'detalles.tipoCambioCompra',

            'eventosLogisticos.tipoEvento',
            'eventosLogisticos.usuario',

            'costos.tipoCosto',
            'costos.moneda',
        ]);

        $productos = Producto::query()
            ->with([
                'marca',
                'categoria',
            ])
            ->where('activo', true)
            ->orderBy('nombre')
            ->orderBy('modelo')
            ->get();

        $categorias =
            CategoriaProducto::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get();

        $marcas = Marca::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $monedas = Moneda::query()
            ->where('activo', true)
            ->whereIn(
                'codigo',
                ['BOB', 'USD']
            )
            ->orderBy('codigo')
            ->get();

        $cantidadEsperada =
            $lote->detalles
                ->sum(
                    'cantidad_esperada'
                );

        $cantidadRecibida =
            $lote->detalles
                ->sum(
                    'cantidad_recibida'
                );

        $referenciaUsdBob = null;

        try {

            $referenciaUsdBob =
                $tipoCambioService
                    ->obtenerReferenciaUsdBob();

        } catch (ReglaNegocioException $exception) {

            $referenciaUsdBob =
                null;
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
    ): RedirectResponse {
        try {

            $loteService->agregarDetalle(
                $request->user()->id,
                $lote->id,
                $request->all()
            );

            return redirect()
                ->route(
                    'importaciones.show',
                    $lote
                )
                ->with(
                    'success',
                    'Producto agregado al lote correctamente.'
                );

        } catch (ReglaNegocioException $exception) {

            return back()
                ->withInput()
                ->withErrors([
                    'detalle' =>
                        $exception->getMessage(),
                ]);
        }
    }
}
