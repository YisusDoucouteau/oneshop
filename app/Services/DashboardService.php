<?php

namespace App\Services;

use App\Models\Equipo;
use App\Models\Almacen;
use App\Models\EstadoEquipo;
use App\Models\Venta;
use App\Models\CasoGarantia;
use App\Models\HistorialEstadoEquipo;
class DashboardService
{
    public function obtenerResumen(): array
    {
        return [

            'totalEquipos' => Equipo::query()
                ->where('activo', true)
                ->count(),


            'disponibles' => Equipo::query()
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
                ->count(),


            'reservados' => Equipo::query()
                ->whereHas(
                    'estadoActual',
                    function ($query) {
                        $query->where(
                            'codigo',
                            'RESERVADO'
                        );
                    }
                )
                ->where('activo', true)
                ->count(),


            'enProceso' => Equipo::query()
                ->whereHas(
                    'estadoActual',
                    function ($query) {
                        $query->whereIn(
                            'codigo',
                            [
                                'PENDIENTE_REVISION',
                                'EN_DIAGNOSTICO',
                                'EN_REPARACION'
                            ]
                        );
                    }
                )
                ->where('activo', true)
                ->count(),


            'porEstado' => EstadoEquipo::query()
                ->withCount([
                    'equipos as cantidad' => function ($query) {
                        $query->where(
                            'activo',
                            true
                        );
                    }
                ])
                ->where('activo', true)
                ->orderBy('orden')
                ->get(),


            'porAlmacen' => Almacen::query()
                ->withCount([
                    'equipos' => function ($query) {
                        $query->where(
                            'activo',
                            true
                        );
                    }
                ])
                ->where('activo', true)
                ->get(),
'totalVentas' => Venta::query()
    ->where('estado', 'ACTIVA')
    ->count(),


'garantiasActivas' => CasoGarantia::query()
    ->count(),


'ultimosMovimientos' => HistorialEstadoEquipo::query()
    ->with([
        'equipo.producto.marca',
        'estadoOrigen',
        'estadoDestino',
        'usuario',
    ])
    ->latest('fecha_cambio')
    ->limit(5)
    ->get(),

            'ultimosEquipos' => Equipo::query()
                ->with([
                    'producto.marca',
                    'estadoActual'
                ])
                ->where('activo', true)
                ->latest('fecha_registro')
                ->limit(5)
                ->get(),
        ];
        
    }
}