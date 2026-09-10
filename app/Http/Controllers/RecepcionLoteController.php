<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleLote;
use App\Models\Lote;
use App\Models\UnidadAdquirida;
use App\Services\RecepcionLoteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecepcionLoteController extends Controller
{

    public function create(
        Lote $lote,
        DetalleLote $detalle
    ): View {


        if ($detalle->lote_id !== $lote->id) {
            abort(404);
        }


        $detalle->load([
            'producto.marca',
            'producto.categoria',
        ]);



        $pendientes =
            max(
                0,
                $detalle->cantidad_esperada
                -
                $detalle->cantidad_recibida
            );



        if ($pendientes <= 0) {

            return redirect()
                ->route(
                    'importaciones.show',
                    $lote
                )
                ->withErrors([
                    'recepcion'
                    =>
                    'No existen unidades pendientes.'
                ]);

        }



        return view(
            'importaciones.recepcion.create',
            compact(
                'lote',
                'detalle',
                'pendientes'
            )
        );

    }

   public function editar(
    UnidadAdquirida $unidad
): View
{

    $unidad->load([
    'producto.marca',
    'detalleLote.lote',
    'moneda',
]);


    return view(
        'importaciones.recepcion.editar',
        compact('unidad')
    );

}

public function actualizar(
    Request $request,
    UnidadAdquirida $unidad
): RedirectResponse {

    $datos = $request->validate([

        'procesador' => [
            'required',
            'string',
            'max:150',
        ],

        'generacion_procesador' => [
            'nullable',
            'string',
            'max:100',
        ],

        'ram_gb' => [
            'nullable',
            'integer',
            'min:1',
        ],

        'almacenamiento_gb' => [
            'nullable',
            'integer',
            'min:1',
        ],

        'tipo_almacenamiento' => [
            'nullable',
            'in:SSD,NVME,HDD',
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

        'tiene_cargador' => [
            'required',
            'boolean',
        ],

        'sistema_operativo' => [
            'nullable',
            'string',
            'max:150',
        ],

        'resolucion' => [
            'nullable',
            'string',
            'max:100',
        ],

        'pantalla_pulgadas' => [
            'nullable',
            'numeric',
            'min:1',
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


    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR UNIDAD
    |--------------------------------------------------------------------------
    */

    $unidad->update([

        'procesador' =>
            $datos['procesador'],

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
            $datos['tiene_cargador'],

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

    ]);


    /*
    |--------------------------------------------------------------------------
    | RECUPERAR EL LOTE REAL
    |--------------------------------------------------------------------------
    */

    $unidad->load(
        'detalleLote.lote'
    );


    $lote =
        $unidad
        ->detalleLote
        ?->lote;


    if (!$lote) {

        return redirect()
            ->route('importaciones.index')
            ->withErrors([

                'edicion' =>
                    'El equipo fue actualizado, pero no se pudo determinar el lote de origen.'

            ]);

    }


    /*
    |--------------------------------------------------------------------------
    | VOLVER AL DETALLE DEL LOTE
    |--------------------------------------------------------------------------
    |
    | IMPORTANTE:
    | Pasamos $lote completo y NO $lote->id.
    |
    */

    return redirect()
        ->route(
            'importaciones.show',
            $lote
        )
        ->with(
            'success',
            'Equipo actualizado correctamente.'
        );

}


    public function store(
        Request $request,
        Lote $lote,
        DetalleLote $detalle,
        RecepcionLoteService $service
    ): RedirectResponse {


        if ($detalle->lote_id !== $lote->id) {
            abort(404);
        }



        $datos =
            $request->validate([


                'cantidad' => [
                    'required',
                    'integer',
                    'min:1'
                ],
                'procesador'=>[
'required',
'string',
'max:100'
],

'ram_gb'=>[
'required',
'integer',
'min:1'
],

'almacenamiento_gb'=>[
'required',
'integer',
'min:1'
],

'tipo_almacenamiento'=>[
'required',
'string'
],

'tiene_cargador'=>[
'required'
],

                'serial_fabricante' => [
                    'nullable',
                    'string',
                    'max:150'
                ],



                'observacion' => [
                    'nullable',
                    'string'
                ],


            ]);



        try {


            $unidad =
                $service->recibirUnidad(
                    $request->user()->id,
                    $detalle->id,
                    $datos
                );



            return redirect()
                ->route(
                    'importaciones.show',
                    $lote
                )
                ->with(
                    'success',
                    'Unidad recibida correctamente: '
                    .
                    $unidad->codigo_trazabilidad
                );



        } catch(ReglaNegocioException $e){


            return back()
                ->withInput()
                ->withErrors([
                    'recepcion'
                    =>
                    $e->getMessage()
                ]);

        }


    }

}