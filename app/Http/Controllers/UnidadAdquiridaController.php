<?php

namespace App\Http\Controllers;

use App\Models\UnidadAdquirida;
use App\Models\Lote;
use App\Models\Almacen;
use App\Models\Moneda;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UnidadAdquiridaController extends Controller
{

    public function index(): View
    {

        $unidades = UnidadAdquirida::query()
            ->with([
                'almacenActual',
                'moneda',
            ])
            ->latest()
            ->paginate(15);


        return view(
            'unidades_adquiridas.index',
            compact('unidades')
        );

    }



    public function create(): View
    {

        $lotes = Lote::query()
            ->latest()
            ->get();


        $monedas = Moneda::query()
            ->where('activo', true)
            ->orderBy('codigo')
            ->get();



        return view(
            'unidades_adquiridas.create',
            compact(
                'lotes',
                'monedas'
            )
        );

    }





    public function store(
        Request $request
    ): RedirectResponse {


        $datos = $request->validate([


            'detalle_lote_id'=>[
                'required',
                'exists:detalles_lotes,id'
            ],


            'nombre_equipo'=>[
                'required',
                'string',
                'max:150'
            ],


            'modelo_equipo'=>[
                'nullable',
                'string',
                'max:150'
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
                'string',
                'max:150'
            ],


            'generacion_procesador'=>[
                'nullable',
                'string',
                'max:80'
            ],


            'ram_gb'=>[
                'nullable',
                'integer'
            ],


            'almacenamiento_gb'=>[
                'nullable',
                'integer'
            ],


            'tarjeta_grafica'=>[
                'nullable',
                'string'
            ],


            'sistema_operativo'=>[
                'nullable',
                'string'
            ],


            'observacion_revision'=>[
                'nullable',
                'string'
            ],


        ]);




        $almacenPendiente =
            Almacen::query()
            ->where(
                'codigo',
                'COMPRAS_PENDIENTES'
            )
            ->first();



        if(!$almacenPendiente){

            return back()
                ->withErrors([
                    'almacen'=>
                    'No existe el almacén COMPRAS_PENDIENTES'
                ])
                ->withInput();

        }




        UnidadAdquirida::create([


            'detalle_lote_id'=>
                $datos['detalle_lote_id'],


            'nombre_equipo'=>
                $datos['nombre_equipo'],


            'modelo_equipo'=>
                $datos['modelo_equipo'] ?? null,


            'precio_compra'=>
                $datos['precio_compra'] ?? null,


            'moneda_id'=>
                $datos['moneda_id'] ?? null,


            'almacen_actual_id'=>
                $almacenPendiente->id,


            'estado'=>
                UnidadAdquirida::ESTADO_PENDIENTE_LLEGADA,



            'procesador'=>
                $datos['procesador'] ?? null,


            'generacion_procesador'=>
                $datos['generacion_procesador'] ?? null,


            'ram_gb'=>
                $datos['ram_gb'] ?? null,


            'almacenamiento_gb'=>
                $datos['almacenamiento_gb'] ?? null,


            'tarjeta_grafica'=>
                $datos['tarjeta_grafica'] ?? null,


            'sistema_operativo'=>
                $datos['sistema_operativo'] ?? null,


            'observacion_revision'=>
                $datos['observacion_revision'] ?? null,


            'registrado_por_id'=>
                $request->user()->id,


        ]);




        return redirect()
            ->route(
                'unidades-adquiridas.index'
            )
            ->with(
                'success',
                'Equipo comprado registrado correctamente.'
            );

    }

}