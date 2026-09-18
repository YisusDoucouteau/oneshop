<?php

namespace App\Http\Controllers;

use App\Models\CostoLote;
use App\Models\Lote;
use App\Models\Moneda;
use App\Services\TipoCambioService;
use Illuminate\Http\Request;

class CostoLoteController extends Controller
{
    public function store(
        Request $request,
        Lote $lote,
        TipoCambioService $tipoCambioService
    ) {

        $datos = $request->validate([

            'tipo_costo_id' => [
                'required',
                'exists:tipos_costos,id',
            ],

            'moneda_id' => [
                'required',
                'exists:monedas,id',
            ],

            'monto_origen' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'tipo_cambio' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'fecha_costo' => [
                'required',
                'date',
            ],

            'referencia' => [
                'nullable',
                'string',
                'max:150',
            ],

            'observacion' => [
                'nullable',
                'string',
            ],

        ]);

        $moneda =
            Moneda::findOrFail(
                $datos['moneda_id']
            );

        $tipoCambio = null;

        if (
            $moneda->codigo !== 'BOB'
        ) {

            if (
                empty($datos['tipo_cambio'])
            ) {

                return back()
                    ->withErrors([
                        'tipo_cambio' => 'Debe ingresar el tipo de cambio.',
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

            'lote_id' => $lote->id,

            'tipo_costo_id' => $datos['tipo_costo_id'],

            'moneda_id' => $moneda->id,

            'tipo_cambio_id' => $tipoCambio?->id,

            'monto_origen' => $datos['monto_origen'],

            'monto_bob' => $montoBob,

            'fecha_costo' => $datos['fecha_costo'],

            'referencia' => $datos['referencia'] ?? null,

            'observacion' => $datos['observacion'] ?? null,

            'registrado_por_id' => $request->user()->id,
            'estado' => 'ACTIVO',

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
    ) {

        $datos = $request->validate([

            'tipo_costo_id' => 'required|exists:tipos_costos,id',

            'moneda_id' => 'required|exists:monedas,id',

            'monto_origen' => 'required|numeric|min:0.01',

            'tipo_cambio' => 'nullable|numeric',

            'fecha_costo' => 'required|date',

            'referencia' => 'nullable|string|max:150',

            'observacion' => 'nullable|string',

        ]);

        $moneda =
        Moneda::findOrFail(
            $datos['moneda_id']
        );

        $tipoCambio = null;

        if ($moneda->codigo != 'BOB') {

            if (empty($datos['tipo_cambio'])) {

                return back()
                    ->withErrors([
                        'tipo_cambio' => 'Debe ingresar el tipo de cambio para '.$moneda->codigo,
                    ]);

            }

            $tipoCambio =
            $tipoCambioService->registrarAplicado(
                $request->user()->id,
                $moneda->codigo,
                (float) $datos['tipo_cambio'],
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

            'tipo_costo_id' => $datos['tipo_costo_id'],

            'moneda_id' => $moneda->id,

            'tipo_cambio_id' => $tipoCambio?->id,

            'monto_origen' => $datos['monto_origen'],

            'monto_bob' => $montoBob,

            'fecha_costo' => $datos['fecha_costo'],

            'referencia' => $datos['referencia'],

            'observacion' => $datos['observacion'],

        ]);

        return back()
            ->with(
                'success',
                'Costo actualizado correctamente.'
            );
    }

    public function anular(
        Request $request,
        CostoLote $costo
    ) {

        $request->validate([
            'motivo_anulacion' => 'nullable|string|max:255',
        ]);

        // Limpia cualquier asignación histórica de la implementación anterior.
        $costo->asignacionesUnidades()->delete();

        /*
        |--------------------------------------------------------------------------
        | ANULAR COSTO
        |--------------------------------------------------------------------------
        */

        $costo->update([

            'estado' => 'ANULADO',

            'motivo_anulacion' => $request->motivo_anulacion,

            'anulado_por_id' => auth()->id(),

            'fecha_anulacion' => now(),

        ]);

        return back()
            ->with(
                'success',
                'Costo de importación anulado correctamente.'
            );

    }
}
