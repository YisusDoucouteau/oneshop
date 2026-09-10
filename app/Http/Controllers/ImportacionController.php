<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\CategoriaProducto;
use App\Models\Lote;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\UnidadAdquirida;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Services\LoteService;
use Illuminate\Http\JsonResponse;
use App\Services\TipoCambioService;
use App\Services\UnidadAdquiridaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;


class ImportacionController extends Controller
{


    public function index(Request $request): View
    {


        $buscar = trim(
            (string)$request->get('buscar', '')
        );


        $estado = trim(
            (string)$request->get('estado', '')
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
                fn ($q) =>
                $q->where(
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

                    'registro' => $e->getMessage()

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

    'detalles.unidadesAdquiridas.producto.marca',
    'detalles.unidadesAdquiridas.moneda',
    'detalles.unidadesAdquiridas.tipoCambio',
    'detalles.unidadesAdquiridas.almacenActual',

]);




        $productos =
            Producto::with([
                'marca',
                'categoria'
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
                    'USDT'
                ]
            )
            ->orderBy('codigo')
            ->get();





        $cantidadEsperada =
            $lote->detalles
            ->sum('cantidad_esperada');




        $cantidadRecibida =
            $lote->detalles
            ->sum('cantidad_recibida');




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


                    'producto' =>

                    trim(
                        ($detalle->producto->marca?->nombre ?? '')
                            . ' ' .
                            $detalle->producto->nombre
                            . ' ' .
                            ($detalle->producto->modelo ?? '')
                    ),


                    'cantidad_esperada' =>

                    $detalle->cantidad_esperada,


                    'cantidad_recibida' =>

                    $detalle->cantidad_recibida


                ]

            ]);
        } catch (\Throwable $e) {


            return response()->json([

                'ok' => false,

                'message' => $e->getMessage()

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
        UnidadAdquiridaService $service,
        TipoCambioService $tipoCambioService
    ): RedirectResponse {


        $datos = $request->validate([


            'detalle_lote_id' => [
                'required',
                'exists:detalles_lotes,id'
            ],


            'cantidad' => [
                'required',
                'integer',
                'in:1'
            ],


            // COMPRA

            'precio_compra' => [
                'nullable',
                'numeric',
                'min:0'
            ],


            'moneda_id' => [
                'nullable',
                'exists:monedas,id'
            ],


            'tipo_cambio_compra' => [
                'nullable',
                'numeric',
                'min:0'
            ],


            'fecha_compra' => [
                'nullable',
                'date'
            ],


            'referencia_compra' => [
                'nullable',
                'string',
                'max:150'
            ],


            'proveedor_compra' => [
                'nullable',
                'string',
                'max:150'
            ],



            // HARDWARE

            'procesador' => [
                'nullable',
                'string'
            ],


            'generacion_procesador' => [
                'nullable',
                'string'
            ],


            'ram_gb' => [
                'nullable',
                'integer'
            ],


            'almacenamiento_gb' => [
                'nullable',
                'integer'
            ],


            'tipo_almacenamiento' => [
                'nullable',
                'string',
                'max:50'
            ],


            'tarjeta_grafica' => [
                'nullable',
                'string',
                'max:150'
            ],


            'serial_fabricante' => [
                'nullable',
                'string',
                'max:150'
            ],


            'tiene_cargador' => [
                'required',
                'boolean'
            ],



            // DATOS EXTRA


            'sistema_operativo' => [
                'nullable',
                'string',
                'max:100'
            ],


            'resolucion' => [
                'nullable',
                'string',
                'max:50'
            ],


            'pantalla_pulgadas' => [
                'nullable',
                'numeric'
            ],


            'servicio_requerido' => [
                'nullable',
                'string'
            ],


            'observacion' => [
                'nullable',
                'string'
            ],

        ]);
        $tipoCambio = null;

        $precioBob = null;
        $datosCompra = [];

        if (
            !empty($datos['precio_compra'])
            &&
            !empty($datos['moneda_id'])
        ) {


            $moneda =
                Moneda::findOrFail(
                    $datos['moneda_id']
                );


            $tipoCambio = null;


            if ($moneda->codigo !== 'BOB') {


                if (empty($datos['tipo_cambio_compra'])) {
                    return back()
                        ->withErrors([
                            'tipo_cambio' =>
                            'Debe ingresar el tipo de cambio.'
                        ])
                        ->withInput();
                }


                $tipoCambio =
                    $tipoCambioService->registrarAplicado(
                        $request->user()->id,
                        $moneda->codigo,
                        $datos['tipo_cambio_compra'],
                        'Compra equipo lote ' . $lote->codigo
                    );
            }


            $precioBob =
                $tipoCambioService->convertirABob(
                    $datos['precio_compra'],
                    $moneda->codigo,
                    $tipoCambio
                );


            $datosCompra = [

                'precio_compra' =>
                $datos['precio_compra'],

                'moneda_id' =>
                $moneda->id,

                'tipo_cambio_compra_id' =>
                $tipoCambio?->id,

                'precio_compra_bob' =>
                $precioBob,


                'fecha_compra' =>
                $datos['fecha_compra'] ?? null,


                'referencia_compra' =>
                $datos['referencia_compra'] ?? null,


                'proveedor_compra' =>
                $datos['proveedor_compra'] ?? null,

            ];
        }

        if (empty($datos['cantidad'])) {

            return back()
                ->withErrors([
                    'cantidad' => 'Cantidad inválida'
                ]);
        }

        $unidades =

            $service->registrarLlegadaCochabamba(

                $request->user()->id,

                $datos['detalle_lote_id'],

                $datos['cantidad'],

                null,

                $datos['observacion'] ?? null,


                [

                    'precio_compra' =>
                    $datos['precio_compra'] ?? null,


                    'moneda_id' =>
                    $datos['moneda_id'] ?? null,


                    'tipo_cambio_compra_id' =>
                    $tipoCambio?->id ?? null,


                    'precio_compra_bob' =>
                    $precioBob ?? null,


                    'fecha_compra' =>
                    $datos['fecha_compra'] ?? null,


                    'referencia_compra' =>
                    $datos['referencia_compra'] ?? null,


                    'proveedor_compra' =>
                    $datos['proveedor_compra'] ?? null,


                ]

            );





        foreach ($unidades as $unidad) {


            $unidad->update([


                'procesador' =>
                $datos['procesador'] ?? null,


                'generacion_procesador' =>
                $datos['generacion_procesador'] ?? null,


                'ram_gb' =>
                $datos['ram_gb'] ?? null,


                'almacenamiento_gb' =>
                $datos['almacenamiento_gb'] ?? null,


                'tipo_almacenamiento' =>
                $datos['tipo_almacenamiento'] ?? null,


                'tarjeta_grafica' =>
                $datos['tarjeta_grafica'] ?? null,


                'serial_fabricante' =>
                $datos['serial_fabricante'] ?? null,


                'tiene_cargador' =>
                $datos['tiene_cargador'] ?? null,

                'sistema_operativo' =>
                $datos['sistema_operativo'] ?? null,


                'resolucion' =>
                $datos['resolucion'] ?? null,


                'pantalla_pulgadas' =>
                $datos['pantalla_pulgadas'] ?? null,


                'servicio_requerido' =>
                $datos['servicio_requerido'] ?? null,


                'observacion_revision' =>
                $datos['observacion'] ?? null,


                'precio_compra' =>
                $datosCompra['precio_compra'] ?? null,


                'moneda_id' =>
                $datosCompra['moneda_id'] ?? null,


                'tipo_cambio_compra_id' =>
                $datosCompra['tipo_cambio_compra_id'] ?? null,


                'precio_compra_bob' =>
                $datosCompra['precio_compra_bob'] ?? null,

            ]);




            $lote->refresh();


            $lote->load([

    'proveedor',

    'costos.tipoCosto',
    'costos.moneda',
    'costos.tipoCambio',

    'detalles.producto.marca',
    'detalles.producto.categoria',

    'detalles.moneda',
    'detalles.tipoCambioCompra',

    'detalles.unidadesAdquiridas.producto.marca',
    'detalles.unidadesAdquiridas.moneda',
    'detalles.unidadesAdquiridas.tipoCambio',
    'detalles.unidadesAdquiridas.almacenActual',

    'detalles.unidadesActivas',

]);


            return redirect()

                ->route(
                    'importaciones.show',
                    $lote
                )

                ->with(
                    'success',
                    'Equipo recibido registrado correctamente.'
                );
        }
    }
    public function anularUnidad(
    Request $request,
    UnidadAdquirida $unidad
)
{


$datos = $request->validate([

    'motivo_anulacion'=>[
        'required',
        'string',
        'max:255'
    ]

]);



if(
    $unidad->estado === UnidadAdquirida::ESTADO_ANULADA
)
{

    return back()
        ->withErrors([
            'unidad'=>'El equipo ya está anulado.'
        ]);

}



$unidad->update([


    'estado'=>
        UnidadAdquirida::ESTADO_ANULADA,


    'motivo_anulacion'=>
        $datos['motivo_anulacion'],


    'anulado_por_id'=>
        auth()->id(),


    'fecha_anulacion'=>
        now(),


]);



return back()

->with(

'success',

'Equipo anulado correctamente.'

);


}
}
