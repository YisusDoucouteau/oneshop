<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\SolicitudDescuento;
use App\Services\GestionSolicitudDescuentoService;
use App\Services\ValidadorVentaPrecioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SolicitudDescuentoController extends Controller
{
    public function store(
        Request $request,
        Equipo $equipo,
        ValidadorVentaPrecioService $validadorVentaPrecioService
    ): RedirectResponse {
        $datos = $request->validate([
            'precio_propuesto' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'motivo' => [
                'required',
                'string',
                'min:5',
                'max:500',
            ],
        ]);

        try {
            $resultado = $validadorVentaPrecioService->validar(
                equipoId: $equipo->id,
                precioPropuesto: (float) $datos['precio_propuesto'],
                clienteId: null,
                vendedorId: $request->user()->id,
                motivoSolicitud: $datos['motivo']
            );

            if (($resultado['estado'] ?? null) === 'AUTORIZADO') {
                return back()->with(
                    'success',
                    'Ese precio ya cuenta con una autorización aprobada.'
                );
            }

            if (!($resultado['requiere_aprobacion'] ?? false)) {
                return back()->withErrors([
                    'autorizacion' => match ($resultado['estado'] ?? null) {
                        'NO_RECOMENDADA' =>
                            'El precio propuesto genera pérdida y no puede enviarse como una autorización ordinaria.',
                        'APROBADO' =>
                            'Ese precio ya se encuentra dentro del rango permitido y no necesita autorización.',
                        default =>
                            'La propuesta todavía no reúne las condiciones para crear una solicitud de autorización.',
                    },
                ]);
            }

            if (!$resultado['solicitud']) {
                return back()->withErrors([
                    'autorizacion' =>
                        'No se pudo crear la solicitud de autorización.',
                ]);
            }

            return back()->with(
                'success',
                'Solicitud enviada a administración. La propuesta queda pendiente de aprobación.'
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'autorizacion' => $exception->getMessage(),
                ]);
        }
    }

    public function index(): View
    {
        $cargar = [
            'precioEquipo.equipo.producto.marca',
            'solicitadoPor',
            'cliente',
            'respondidoPor',
        ];

        $pendientes = SolicitudDescuento::query()
            ->with($cargar)
            ->where('estado', 'PENDIENTE')
            ->orderBy('created_at')
            ->get();

        $resueltas = SolicitudDescuento::query()
            ->with($cargar)
            ->whereIn('estado', [
                'APROBADA',
                'RECHAZADA',
            ])
            ->latest('fecha_respuesta')
            ->limit(30)
            ->get();

        return view(
            'precios.autorizaciones.index',
            compact(
                'pendientes',
                'resueltas'
            )
        );
    }

    public function aprobar(
        Request $request,
        SolicitudDescuento $solicitud,
        GestionSolicitudDescuentoService $gestionSolicitudDescuentoService
    ): RedirectResponse {
        $datos = $request->validate([
            'medio_respuesta' => [
                'required',
                'in:SISTEMA,LLAMADA,WHATSAPP,PRESENCIAL',
            ],
            'motivo_respuesta' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        try {
            $gestionSolicitudDescuentoService->aprobar(
                $solicitud->id,
                $request->user()->id,
                $datos['motivo_respuesta'] ?? null,
                $datos['medio_respuesta']
            );

            return back()->with(
                'success',
                'Descuento autorizado correctamente.'
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'autorizacion' => $exception->getMessage(),
            ]);
        }
    }

    public function rechazar(
        Request $request,
        SolicitudDescuento $solicitud,
        GestionSolicitudDescuentoService $gestionSolicitudDescuentoService
    ): RedirectResponse {
        $datos = $request->validate([
            'medio_respuesta' => [
                'required',
                'in:SISTEMA,LLAMADA,WHATSAPP,PRESENCIAL',
            ],
            'motivo_respuesta' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        try {
            $gestionSolicitudDescuentoService->rechazar(
                $solicitud->id,
                $request->user()->id,
                $datos['motivo_respuesta'],
                $datos['medio_respuesta']
            );

            return back()->with(
                'success',
                'Solicitud rechazada correctamente.'
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors([
                'autorizacion' => $exception->getMessage(),
            ]);
        }
    }
}
