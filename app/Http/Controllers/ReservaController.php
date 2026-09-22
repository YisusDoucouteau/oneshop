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

    public function index(): View
    {
        $reservas = Reserva::query()
            ->with([
                'cliente',
                'detalles.equipo.producto',
            ])
            ->latest()
            ->paginate(15);

        return view(
            'reservas.index',
            compact('reservas')
        );
    }

    public function create(): View
    {
        $clientes = Cliente::query()
            ->where('activo', true)
            ->orderBy('nombre_completo')
            ->get();

        $equipos = Equipo::query()
            ->with([
                'producto',
                'estadoActual',
                'precioVigente',
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
            ->where('activo', true)
            ->get();

        $diasMaximosReserva =
            $this->reservaService
                ->obtenerDiasMaximosEstandar();

        return view(
            'reservas.create',
            compact(
                'clientes',
                'equipos',
                'diasMaximosReserva'
            )
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $datos = $request->validate([
            'cliente_id' => [
                'required',
                'exists:clientes,id',
            ],

            'equipos' => [
                'required',
                'array',
                'min:1',
            ],

            'equipos.*' => [
                'required',
                'exists:equipos,id',
            ],

            'precios_acordados' => [
                'nullable',
                'array',
            ],

            'precios_acordados.*' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],

            'fecha_expiracion' => [
                'required',
                'date',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $reserva =
            $this->reservaService
                ->crearReserva(
                    clienteId:
                        (int) $datos['cliente_id'],

                    usuarioId:
                        $request->user()->id,

                    equiposIds:
                        $datos['equipos'],

                    fechaVencimiento:
                        Carbon::parse(
                            $datos['fecha_expiracion']
                        ),

                    observacion:
                        $datos['observacion']
                        ?? null,

                    preciosAcordados:
                        $datos['precios_acordados']
                        ?? []
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

    public function show(
        Reserva $reserva
    ): View {
        $reserva->load([
            'cliente',
            'registradoPor',
            'detalles.equipo.producto',
            'pagos',
            'prorrogas',
            'venta',
        ]);

        return view(
            'reservas.show',
            compact('reserva')
        );
    }

    public function cancelar(
        Request $request,
        Reserva $reserva
    ): RedirectResponse {
        $this->reservaService
            ->liberarReserva(
                reservaId:
                    $reserva->id,

                usuarioId:
                    $request->user()->id
            );

        return back()
            ->with(
                'success',
                'Reserva liberada correctamente.'
            );
    }

    public function convertirVenta(
        Request $request,
        Reserva $reserva
    ): RedirectResponse {
        $venta =
            $this->ventaService
                ->convertirReservaEnVenta(
                    reservaId:
                        $reserva->id,

                    vendedorId:
                        $request->user()->id
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
