<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleLote;
use App\Models\Lote;
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