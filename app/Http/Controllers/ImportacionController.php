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
            (string)$request->get('buscar','')
        );


        $estado = trim(
            (string)$request->get('estado','')
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
                function($query) use ($buscar){

                    $query->where(function($sub) use($buscar){

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
                fn($q)=>
                    $q->where(
                        'estado',
                        $estado
                    )
            )


            ->latest('id')

            ->paginate(15)

            ->withQueryString();



        $resumen=[

            'total'=>Lote::count(),

            'abiertos'=>Lote::where(
                'estado',
                'ABIERTO'
            )->count(),

            'parciales'=>Lote::where(
                'estado',
                'RECEPCION_PARCIAL'
            )->count(),

            'recibidos'=>Lote::where(
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
    ): RedirectResponse
    {


        try{


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



        }catch(ReglaNegocioException $e){


            return back()

                ->withInput()

                ->withErrors([

                    'registro'=>$e->getMessage()

                ]);

        }


    }








    public function show(
        Lote $lote,
        TipoCambioService $tipoCambioService
    ): View
    {


        $lote->load([


            'proveedor',


            'detalles.producto.marca',

            'detalles.producto.categoria',


            'detalles.moneda',

            'detalles.tipoCambioCompra',


            'detalles.unidadesAdquiridas.producto.marca',

            'detalles.unidadesAdquiridas.almacenActual',


            'eventosLogisticos.tipoEvento',

            'eventosLogisticos.usuario',


            'costos.tipoCosto',

            'costos.moneda',


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




        $referenciaUsdBob=null;



        try{


            $referenciaUsdBob =
                $tipoCambioService
                ->obtenerReferenciaUsdBob();



        }catch(ReglaNegocioException $e){


            $referenciaUsdBob=null;


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

    'ok'=>true,

    'detalle'=>[

        'id'=>$detalle->id,


        'producto'=>

            trim(
                ($detalle->producto->marca?->nombre ?? '')
                .' '.
                $detalle->producto->nombre
                .' '.
                ($detalle->producto->modelo ?? '')
            ),


        'cantidad_esperada'=>

            $detalle->cantidad_esperada,


        'cantidad_recibida'=>

            $detalle->cantidad_recibida


    ]

]);



    }catch(\Throwable $e){


        return response()->json([

            'ok'=>false,

            'message'=>$e->getMessage()

        ],422);


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

    ): RedirectResponse
    {


        $datos =
            $request->validate([


                'detalle_lote_id'=>[
                    'required',
                    'exists:detalles_lotes,id'
                ],


                'cantidad'=>[
    'required',
    'integer',
    'in:1'
],


                'precio_compra'=>[
                    'nullable',
                    'numeric',
                    'min:0'
                ],


                'moneda_id'=>[
                    'nullable',
                    'exists:monedas,id'
                ],


                'procesador'=>[
                    'nullable',
                    'string'
                ],


                'generacion_procesador'=>[
                    'nullable',
                    'string'
                ],


                'ram_gb'=>[
                    'nullable',
                    'integer'
                ],


                'almacenamiento_gb'=>[
                    'nullable',
                    'integer'
                ],


                'observacion'=>[
                    'nullable',
                    'string'
                ],


            ]);





        $unidades =

            $service->registrarLlegadaCochabamba(

                $request->user()->id,

                $datos['detalle_lote_id'],

                $datos['cantidad'],

                null,

                $datos['observacion'] ?? null

            );





        foreach($unidades as $unidad){


            $unidad->update([


                'procesador'=>
                    $datos['procesador'] ?? null,


                'generacion_procesador'=>
                    $datos['generacion_procesador'] ?? null,


                'ram_gb'=>
                    $datos['ram_gb'] ?? null,


                'almacenamiento_gb'=>
                    $datos['almacenamiento_gb'] ?? null,


            ]);


        }





        return redirect()

            ->route(
                'importaciones.show',
                $lote
            )

            ->with(
                'success',
                'Equipos registrados correctamente.'
            );


    }


}