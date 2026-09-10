<?php

namespace App\Http\Controllers;

use App\Models\Lote;
use App\Models\CostoLote;
use App\Models\Moneda;
use App\Services\TipoCambioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AsignacionCostoService;
use Throwable;

class CostoLoteController extends Controller
{


    public function store(
        Request $request,
        Lote $lote,
        TipoCambioService $tipoCambioService
    )
    {


        $datos = $request->validate([

            'tipo_costo_id'=>[
                'required',
                'exists:tipos_costos,id'
            ],

            'moneda_id'=>[
                'required',
                'exists:monedas,id'
            ],

            'monto_origen'=>[
                'required',
                'numeric',
                'min:0.01'
            ],

            'tipo_cambio'=>[
                'nullable',
                'numeric',
                'min:0'
            ],

            'fecha_costo'=>[
                'required',
                'date'
            ],

            'referencia'=>[
                'nullable',
                'string',
                'max:150'
            ],

            'observacion'=>[
                'nullable',
                'string'
            ],

        ]);




        $moneda =
            Moneda::findOrFail(
                $datos['moneda_id']
            );



        $tipoCambio = null;



        if(
            $moneda->codigo !== 'BOB'
        ){


            if(
                empty($datos['tipo_cambio'])
            ){

                return back()
                    ->withErrors([
                        'tipo_cambio'=>
                        'Debe ingresar el tipo de cambio.'
                    ]);

            }



            $tipoCambio =
                $tipoCambioService
                ->registrarAplicado(
                    $request->user()->id,
                    $moneda->codigo,
                    $datos['tipo_cambio'],
                    'Costo de importación lote '.$lote->codigo
                );



        }



        $montoBob =
            $tipoCambioService
            ->convertirABob(
                $datos['monto_origen'],
                $moneda->codigo,
                $tipoCambio
            );




        CostoLote::create([


            'lote_id'=>
                $lote->id,


            'tipo_costo_id'=>
                $datos['tipo_costo_id'],


            'moneda_id'=>
                $moneda->id,


            'tipo_cambio_id'=>
                $tipoCambio?->id,


            'monto_origen'=>
                $datos['monto_origen'],


            'monto_bob'=>
                $montoBob,


            'fecha_costo'=>
                $datos['fecha_costo'],


            'referencia'=>
                $datos['referencia'] ?? null,


            'observacion'=>
                $datos['observacion'] ?? null,


            'registrado_por_id'=>$request->user()->id,
                'estado'=>'ACTIVO',

        ]);



        return back()
            ->with(
                'success',
                'Costo registrado correctamente.'
            );


    }
public function update(
Request $request,
CostoLote $costo,
TipoCambioService $tipoCambioService
)
{

$datos=$request->validate([

'tipo_costo_id'=>'required|exists:tipos_costos,id',

'moneda_id'=>'required|exists:monedas,id',

'monto_origen'=>'required|numeric|min:0.01',

'tipo_cambio'=>'nullable|numeric',

'fecha_costo'=>'required|date',

'referencia'=>'nullable|string|max:150',

'observacion'=>'nullable|string',

]);


$moneda =
Moneda::findOrFail(
$datos['moneda_id']
);


$tipoCambio=null;


if($moneda->codigo != 'BOB'){


    if(empty($datos['tipo_cambio'])){

        return back()
        ->withErrors([
            'tipo_cambio' =>
            'Debe ingresar el tipo de cambio para '.$moneda->codigo
        ]);

    }



    $tipoCambio =
    $tipoCambioService->registrarAplicado(
        $request->user()->id,
        $moneda->codigo,
        (float)$datos['tipo_cambio'],
        'Actualización costo lote'
    );


}


$montoBob =
$tipoCambioService->convertirABob(
$datos['monto_origen'],
$moneda->codigo,
$tipoCambio
);



$costo->update([

'tipo_costo_id'=>$datos['tipo_costo_id'],

'moneda_id'=>$moneda->id,

'tipo_cambio_id'=>$tipoCambio?->id,

'monto_origen'=>$datos['monto_origen'],

'monto_bob'=>$montoBob,

'fecha_costo'=>$datos['fecha_costo'],

'referencia'=>$datos['referencia'],

'observacion'=>$datos['observacion'],

]);

$mensaje =
'Costo actualizado correctamente';


if(
    $costo->asignacionesUnidades()->exists()
){

    $mensaje .=
    ' Este costo tenía distribución asignada. Ejecute "Distribuir costos" para actualizar los equipos.';
}


return back()
->with(
    'success',
    $mensaje
);
}
public function anular(
    Request $request,
    CostoLote $costo,
    AsignacionCostoService $asignacionCostoService
)
{

    $request->validate([
    'motivo_anulacion'=>'nullable|string|max:255'
]);


    /*
    |--------------------------------------------------------------------------
    | ELIMINAR DISTRIBUCIÓN DEL COSTO ANULADO
    |--------------------------------------------------------------------------
    */

    $costo->asignacionesUnidades()->delete();



    /*
    |--------------------------------------------------------------------------
    | ANULAR COSTO
    |--------------------------------------------------------------------------
    */

    $costo->update([

        'estado'=>'ANULADO',

        'motivo_anulacion'=>
            $request->motivo_anulacion,

        'anulado_por_id'=>
            auth()->id(),

        'fecha_anulacion'=>
            now(),

    ]);





    /*
    |--------------------------------------------------------------------------
    | REDISTRIBUIR COSTOS ACTIVOS RESTANTES
    |--------------------------------------------------------------------------
    */


    $costosActivos =
        $costo->lote
            ->costos()
            ->where(
                'estado',
                'ACTIVO'
            )
            ->get();



    foreach($costosActivos as $costoActivo){


        $asignacionCostoService
            ->distribuirPorUnidad(
                $costoActivo
            );


    }





    return back()
        ->with(
            'success',
            'Costo anulado y distribución actualizada correctamente.'
        );

}
public function distribuir(
    Lote $lote,
    AsignacionCostoService $asignacionCostoService
)
{

    /*
    |--------------------------------------------------------------------------
    | COSTOS ACTIVOS DEL LOTE
    |--------------------------------------------------------------------------
    */

    $costos = $lote
        ->costos()

        ->where(
            'estado',
            'ACTIVO'
        )

        ->get();



    if ($costos->isEmpty()) {

        return back()
            ->withErrors([

                'costos' =>
                    'No existen costos activos para distribuir.'

            ]);

    }



    try {


        foreach ($costos as $costo) {

            $asignacionCostoService
                ->distribuirPorUnidad(
                    $costo
                );

        }



        return redirect()

            ->route(
                'importaciones.show',
                $lote
            )

            ->with(
                'success',
                'Costos de importación distribuidos correctamente.'
            );


    } catch (Throwable $e) {


        return back()

            ->withErrors([

                'costos' =>
                    $e->getMessage()

            ]);

    }

}
}