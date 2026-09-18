<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\Cliente;
use App\Models\Equipo;
use App\Services\ReservaService;
use App\Services\VentaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReservaController extends Controller
{

    public function __construct(
        private readonly ReservaService $reservaService,
        private readonly VentaService $ventaService
    ) {
    }



    /**
     * Listado de reservas
     */
    public function index(): View
    {

        $reservas = Reserva::query()
            ->with([
                'cliente',
                'detalles.equipo.producto'
            ])
            ->latest()
            ->paginate(15);



        return view(
            'reservas.index',
            compact('reservas')
        );

    }





    /**
     * Formulario crear reserva
     */
    public function create(): View
    {

        $clientes = Cliente::query()
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();



        $equipos = Equipo::query()
            ->with([
                'producto',
                'estadoActual'
            ])
            ->whereHas(
                'estadoActual',
                function ($query) {

                    $query->where(
                        'codigo',
                        'DISPONIBLE'
                    );

                }
            )
            ->where(
                'activo',
                true
            )
            ->get();



        return view(
            'reservas.create',
            compact(
                'clientes',
                'equipos'
            )
        );

    }





    /**
     * Guardar reserva
     */
    public function store(
        Request $request
    ): RedirectResponse {


        $datos = $request->validate([


            'cliente_id' => [
                'required',
                'exists:clientes,id'
            ],


            'equipos' => [
                'required',
                'array',
                'min:1'
            ],


            'equipos.*' => [
                'required',
                'exists:equipos,id'
            ],


            'fecha_expiracion' => [
                'required',
                'date'
            ],


            'observacion' => [
                'nullable',
                'string'
            ],


        ]);




        $reserva =
            $this->reservaService
            ->crearReserva(

                clienteId:
                    $datos['cliente_id'],

                usuarioId:
                    auth()->id() ?? 1,

                equiposIds:
                    $datos['equipos'],

                fechaVencimiento:
                    Carbon::parse(
                        $datos['fecha_expiracion']
                    ),

                observacion:
                    $datos['observacion'] ?? null

            );





        return redirect()
            ->route(
                'reservas.show',
                $reserva
            )
            ->with(
                'success',
                'Reserva creada correctamente.'
            );

    }





    /**
     * Mostrar detalle
     */
    public function show(
        Reserva $reserva
    ): View {


        $reserva->load([

            'cliente',

            'registradoPor',

            'detalles.equipo.producto',

            'pagos',

            'prorrogas',

            'venta'

        ]);



        return view(
            'reservas.show',
            compact('reserva')
        );

    }





    /**
     * Cancelar / liberar reserva
     */
    public function cancelar(
        Reserva $reserva
    ): RedirectResponse {


        $this->reservaService
            ->liberarReserva(

                reservaId:
                    $reserva->id,

                usuarioId:
                    auth()->id()

            );



        return back()
            ->with(
                'success',
                'Reserva liberada correctamente.'
            );

    }





    /**
     * Convertir reserva en venta
     */
    public function convertirVenta(
        Reserva $reserva
    ): RedirectResponse {


        $venta = $this->ventaService
            ->convertirReservaEnVenta(

                reservaId:
                    $reserva->id,

                vendedorId:
    auth()->id() ?? 1

            );



        return redirect()
            ->route(
                'ventas.show',
                $venta
            )
            ->with(
                'success',
                'Reserva convertida en venta correctamente.'
            );

    }

}
