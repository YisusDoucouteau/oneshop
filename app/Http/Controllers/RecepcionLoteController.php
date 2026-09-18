<?php

namespace App\Http\Controllers;

use App\Models\UnidadAdquirida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecepcionLoteController extends Controller
{
    public function editar(
        UnidadAdquirida $unidad
    ): View|RedirectResponse {
        $unidad->load([
            'producto.marca',
            'detalleLote.lote',
            'moneda',
            'tipoCambioCompra',
        ]);

        if (! $this->puedeModificarRecepcion($unidad)) {
            $lote = $unidad->detalleLote?->lote;

            return $lote
                ? redirect()
                    ->route('importaciones.show', $lote)
                    ->withErrors([
                        'edicion' => 'La recepción ya fue cerrada para esta unidad. Debe gestionarse desde su etapa logística actual.',
                    ])
                : redirect()
                    ->route('importaciones.index')
                    ->withErrors([
                        'edicion' => 'La recepción ya no puede modificarse.',
                    ]);
        }

        return view(
            'importaciones.recepcion.editar',
            compact('unidad')
        );
    }

    public function actualizar(
        Request $request,
        UnidadAdquirida $unidad
    ): RedirectResponse {
        $unidad->loadMissing('detalleLote.lote');

        if (! $this->puedeModificarRecepcion($unidad)) {
            return back()->withErrors([
                'edicion' => 'La recepción ya fue cerrada para esta unidad y no puede modificarse desde este módulo.',
            ]);
        }

        $datos = $request->validate([
            'procesador' => [
                'required',
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
            ],
            'almacenamiento_gb' => [
                'nullable',
                'integer',
                'min:0',
            ],
            'tipo_almacenamiento' => [
                'nullable',
                'in:SSD,NVME,HDD,EMMC',
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
            'grado_recibido' => [
                'required',
                'in:A,B,C',
            ],
            'tiene_cargador' => [
                'required',
                'boolean',
            ],
            'sistema_operativo' => [
                'nullable',
                'string',
                'max:100',
            ],
            'resolucion' => [
                'nullable',
                'string',
                'max:50',
            ],
            'pantalla_pulgadas' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'servicio_requerido' => [
                'nullable',
                'string',
                'max:255',
            ],
            'observacion_revision' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $servicioRequerido = trim(
            (string) ($datos['servicio_requerido'] ?? '')
        );

        $unidad->update([
            'procesador' => $datos['procesador'],
            'generacion_procesador' => $datos['generacion_procesador'] ?? null,
            'ram_gb' => $datos['ram_gb'] ?? null,
            'almacenamiento_gb' => $datos['almacenamiento_gb'] ?? null,
            'tipo_almacenamiento' => $datos['tipo_almacenamiento'] ?? null,
            'tarjeta_grafica' => $datos['tarjeta_grafica'] ?? null,
            'serial_fabricante' => $datos['serial_fabricante'] ?? null,
            'grado_recibido' => $datos['grado_recibido'],
            'tiene_cargador' => $datos['tiene_cargador'],
            'sistema_operativo' => $datos['sistema_operativo'] ?? null,
            'resolucion' => $datos['resolucion'] ?? null,
            'pantalla_pulgadas' => $datos['pantalla_pulgadas'] ?? null,
            'requiere_servicio' => $servicioRequerido !== '',
            'servicio_requerido' => $servicioRequerido !== ''
                ? $servicioRequerido
                : null,
            'observacion_revision' => $datos['observacion_revision'] ?? null,
        ]);

        $lote = $unidad->detalleLote?->lote;

        if (! $lote) {
            return redirect()
                ->route('importaciones.index')
                ->withErrors([
                    'edicion' => 'El equipo fue actualizado, pero no se pudo determinar el lote de origen.',
                ]);
        }

        return redirect()
            ->route('importaciones.show', $lote)
            ->with(
                'success',
                'Equipo actualizado correctamente.'
            );
    }

    private function puedeModificarRecepcion(
        UnidadAdquirida $unidad
    ): bool {
        return in_array(
            $unidad->estado,
            [
                UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                UnidadAdquirida::ESTADO_EN_REVISION,
                UnidadAdquirida::ESTADO_EN_PREPARACION,
            ],
            true
        );
    }
}
