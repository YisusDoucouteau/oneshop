<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\CambioEquipo;
use App\Models\MovimientoAjusteGarantia;
use App\Services\AjusteGarantiaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AjusteGarantiaController extends Controller
{
    public function __construct(
        private readonly AjusteGarantiaService $ajusteGarantiaService
    ) {
    }

    public function store(
        Request $request,
        CambioEquipo $cambio
    ): RedirectResponse {
        $datos = $request->validate([
            'metodo_pago_id' => ['required', 'integer', 'exists:metodos_pago,id'],
            'monto' => ['required', 'numeric', 'gt:0'],
            'referencia' => ['nullable', 'string', 'max:150'],
            'comprobante' => ['nullable', 'string', 'max:500'],
            'observacion' => ['nullable', 'string', 'max:3000'],
        ]);

        try {
            $movimiento = $this->ajusteGarantiaService->registrarMovimiento(
                cambioEquipoId: $cambio->id,
                metodoPagoId: (int) $datos['metodo_pago_id'],
                monto: $datos['monto'],
                registradoPorId: (int) $request->user()->id,
                referencia: $datos['referencia'] ?? null,
                comprobante: $datos['comprobante'] ?? null,
                observacion: $datos['observacion'] ?? null
            );
        } catch (ReglaNegocioException $exception) {
            return $this->redirigirAlCaso($cambio)
                ->withInput()
                ->withErrors([
                    'ajuste_garantia' => $exception->getMessage(),
                ]);
        }

        $accion = $movimiento->tipo_movimiento === 'DEVOLUCION'
            ? 'Devolución'
            : 'Cobro';

        return $this->redirigirAlCaso($cambio)->with(
            'success',
            "{$accion} registrado correctamente."
        );
    }

    public function verificar(
        Request $request,
        CambioEquipo $cambio,
        MovimientoAjusteGarantia $movimiento
    ): RedirectResponse {
        if ((int) $movimiento->cambio_equipo_id !== (int) $cambio->id) {
            abort(404);
        }

        try {
            $this->ajusteGarantiaService->verificarMovimiento(
                movimientoId: $movimiento->id,
                verificadoPorId: (int) $request->user()->id
            );
        } catch (ReglaNegocioException $exception) {
            return $this->redirigirAlCaso($cambio)->withErrors([
                'gestion_ajuste_garantia' => $exception->getMessage(),
            ]);
        }

        return $this->redirigirAlCaso($cambio)->with(
            'success',
            'Movimiento económico verificado correctamente.'
        );
    }

    public function rechazar(
        Request $request,
        CambioEquipo $cambio,
        MovimientoAjusteGarantia $movimiento
    ): RedirectResponse {
        if ((int) $movimiento->cambio_equipo_id !== (int) $cambio->id) {
            abort(404);
        }

        $datos = $request->validate([
            'motivo_rechazo' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
        ]);

        try {
            $this->ajusteGarantiaService->rechazarMovimiento(
                movimientoId: $movimiento->id,
                verificadoPorId: (int) $request->user()->id,
                motivo: $datos['motivo_rechazo']
            );
        } catch (ReglaNegocioException $exception) {
            return $this->redirigirAlCaso($cambio)
                ->withInput()
                ->withErrors([
                    'gestion_ajuste_garantia' => $exception->getMessage(),
                ]);
        }

        return $this->redirigirAlCaso($cambio)->with(
            'success',
            'Movimiento económico rechazado correctamente. El importe volvió a quedar disponible.'
        );
    }

    private function redirigirAlCaso(
        CambioEquipo $cambio
    ): RedirectResponse {
        return redirect()->route(
            'garantias.casos.show',
            [
                'caso' => $cambio->caso_garantia_id,
            ]
        );
    }
}
