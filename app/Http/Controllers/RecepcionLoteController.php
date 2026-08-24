<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CondicionFisica;
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
        /*
        |--------------------------------------------------------------------------
        | Integridad lote - detalle
        |--------------------------------------------------------------------------
        */

        if ($detalle->lote_id !== $lote->id) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Cargamos información necesaria
        |--------------------------------------------------------------------------
        */

        $detalle->load([
            'producto.marca',
            'producto.categoria',
            'moneda',
            'tipoCambioCompra',
        ]);

        /*
         * Este flujo registra unidades físicas individualizadas.
         * Los productos no serializados se manejarán mediante existencias.
         */
        if (!$detalle->producto->es_serializado) {
            return redirect()
                ->route(
                    'importaciones.show',
                    $lote
                )
                ->withErrors([
                    'recepcion' =>
                        'Este producto no se registra por unidad física. Su recepción debe gestionarse mediante existencias.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Control de cantidad
        |--------------------------------------------------------------------------
        */

        $pendientes = max(
            0,
            $detalle->cantidad_esperada
                - $detalle->cantidad_recibida
        );

        if ($pendientes <= 0) {
            return redirect()
                ->route(
                    'importaciones.show',
                    $lote
                )
                ->withErrors([
                    'recepcion' =>
                        'Ya se recibió la cantidad esperada de esta línea de compra.',
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Catálogos para recepción
        |--------------------------------------------------------------------------
        */

        $almacenes = Almacen::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $condiciones = CondicionFisica::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view(
            'importaciones.recepcion.create',
            compact(
                'lote',
                'detalle',
                'almacenes',
                'condiciones',
                'pendientes'
            )
        );
    }


    public function store(
        Request $request,
        Lote $lote,
        DetalleLote $detalle,
        RecepcionLoteService $recepcionLoteService
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Integridad lote - detalle
        |--------------------------------------------------------------------------
        */

        if ($detalle->lote_id !== $lote->id) {
            abort(404);
        }

        /*
        |--------------------------------------------------------------------------
        | Validación HTTP
        |--------------------------------------------------------------------------
        |
        | RegistroEquipoService volverá a validar en dominio.
        | Aquí buscamos errores amigables antes de llamar al servicio.
        |
        */

        $datos = $request->validate([
            'codigo_interno' => [
                'required',
                'string',
                'max:50',
            ],

            'serial_fabricante' => [
                'nullable',
                'string',
                'max:150',
            ],

            'almacen_actual_id' => [
                'required',
                'integer',
            ],

            'condicion_fisica_id' => [
                'nullable',
                'integer',
            ],

            'observacion' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Especificaciones técnicas
            |--------------------------------------------------------------------------
            */

            'procesador' => [
                'nullable',
                'string',
                'max:150',
            ],

            'generacion_procesador' => [
                'nullable',
                'string',
                'max:80',
            ],

            'ram_gb' => [
                'nullable',
                'integer',
                'min:0',
                'max:65535',
            ],

            'almacenamiento_gb' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'tipo_almacenamiento' => [
                'nullable',
                'string',
                'max:50',
            ],

            'tarjeta_grafica' => [
                'nullable',
                'string',
                'max:150',
            ],

            'pantalla_pulgadas' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999.9',
            ],

            'resolucion' => [
                'nullable',
                'string',
                'max:50',
            ],

            'sistema_operativo' => [
                'nullable',
                'string',
                'max:100',
            ],

            'bateria_porcentaje' => [
                'nullable',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        try {
            $equipo = $recepcionLoteService->recibirEquipo(
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
                    'Equipo '
                        . $equipo->codigo_interno
                        . ' recibido correctamente.'
                );

        } catch (ReglaNegocioException $exception) {
            return back()
                ->withInput()
                ->withErrors([
                    'recepcion' =>
                        $exception->getMessage(),
                ]);
        }
    }
}