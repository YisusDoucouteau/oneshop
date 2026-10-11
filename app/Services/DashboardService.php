<?php

namespace App\Services;

use App\Models\Almacen;
use App\Models\CasoGarantia;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\HistorialEstadoEquipo;
use App\Models\Pago;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class DashboardService
{
    public function __construct(
        private readonly IndicadorComercialService $indicadorComercialService
    ) {
    }

    public function obtenerResumen(?User $usuario = null): array
    {
        $equiposBase = $this->equiposBase($usuario);

        $totalEquipos = (clone $equiposBase)->count();

        $disponibles = (clone $equiposBase)
            ->whereHas(
                'estadoActual',
                fn ($query) => $query->where('codigo', 'DISPONIBLE')
            )
            ->count();

        $reservados = (clone $equiposBase)
            ->whereHas(
                'estadoActual',
                fn ($query) => $query->where('codigo', 'RESERVADO')
            )
            ->count();

        $casosGarantiaAbiertos = CasoGarantia::query()
            ->where('estado', '!=', 'CERRADO')
            ->when(
                $this->usuarioRestringidoPorAlmacen($usuario),
                fn (Builder $query) => $query->whereHas(
                    'equipoAfectado',
                    fn (Builder $equipo) => $usuario->almacen_operativo_id === null
                        ? $equipo->whereRaw('1 = 0')
                        : $equipo->where(
                            'almacen_actual_id',
                            $usuario->almacen_operativo_id
                        )
                )
            )
            ->count();

        $porEstado = EstadoEquipo::query()
            ->withCount([
                'equipos as cantidad' => function ($query) use ($usuario) {
                    $query->where('activo', true);
                    $this->aplicarRestriccionAlmacen($query, $usuario);
                },
            ])
            ->where('activo', true)
            ->orderBy('orden')
            ->get();

        $porAlmacen = Almacen::query()
            ->withCount([
                'equipos' => function ($query) {
                    $query->where('activo', true);
                },
            ])
            ->where('activo', true)
            ->when(
                $this->usuarioRestringidoPorAlmacen($usuario),
                fn (Builder $query) => $usuario->almacen_operativo_id === null
                    ? $query->whereRaw('1 = 0')
                    : $query->whereKey($usuario->almacen_operativo_id)
            )
            ->get();

        $ultimosMovimientos = HistorialEstadoEquipo::query()
            ->with([
                'equipo.producto.marca',
                'estadoOrigen',
                'estadoDestino',
                'usuario',
            ])
            ->when(
                $this->usuarioRestringidoPorAlmacen($usuario),
                fn (Builder $query) => $query->whereHas(
                    'equipo',
                    fn (Builder $equipo) => $usuario->almacen_operativo_id === null
                        ? $equipo->whereRaw('1 = 0')
                        : $equipo->where(
                            'almacen_actual_id',
                            $usuario->almacen_operativo_id
                        )
                )
            )
            ->latest('fecha_cambio')
            ->limit(5)
            ->get();

        $ultimosEquipos = $this->equiposBase($usuario)
            ->with([
                'producto.marca',
                'estadoActual',
            ])
            ->latest('fecha_registro')
            ->limit(5)
            ->get();

        $puedeVerReportes = $usuario?->tienePermiso('reportes.ver') ?? false;

        $resumenComercial = null;
        $indicadoresDecision = null;

        if ($puedeVerReportes) {
            $filtrosMes = [
                'fecha_desde' => now()->startOfMonth()->toDateString(),
                'fecha_hasta' => now()->endOfMonth()->toDateString(),
                'estado_venta' => 'REGISTRADA',
            ];

            $filtrosMesAnterior = [
                'fecha_desde' => now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'fecha_hasta' => now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
                'estado_venta' => 'REGISTRADA',
            ];

            $resumenComercial = $this->indicadorComercialService
                ->resumenGeneral($filtrosMes);

            $resumenAnterior = $this->indicadorComercialService
                ->resumenGeneral($filtrosMesAnterior);

            $baseAnterior = (float) $resumenAnterior['total_vendido'];
            $variacionVentas = abs($baseAnterior) > 0.00001
                ? round(
                    (((float) $resumenComercial['total_vendido'] - $baseAnterior)
                        / abs($baseAnterior)) * 100,
                    1
                )
                : null;

            $vendedorDestacado = $this->indicadorComercialService
                ->ventasPorVendedor($filtrosMes)
                ->first();

            $pagosPendientes = Pago::query()
                ->where('estado', 'PENDIENTE')
                ->get(['id', 'monto']);

            $indicadoresDecision = [
                'variacion_ventas' => $variacionVentas,
                'reservas_activas' => Reserva::query()
                    ->where('estado', 'ACTIVA')
                    ->count(),
                'pagos_pendientes' => $pagosPendientes->count(),
                'monto_pendiente' => round(
                    (float) $pagosPendientes->sum('monto'),
                    2
                ),
                'vendedor_destacado' => $vendedorDestacado,
                'disponibilidad_porcentaje' => $totalEquipos > 0
                    ? round(($disponibles / $totalEquipos) * 100, 1)
                    : 0,
            ];
        }

        return [
            'totalEquipos' => $totalEquipos,
            'disponibles' => $disponibles,
            'reservados' => $reservados,
            'casosGarantiaAbiertos' => $casosGarantiaAbiertos,
            'porEstado' => $porEstado,
            'porAlmacen' => $porAlmacen,
            'ultimosMovimientos' => $ultimosMovimientos,
            'ultimosEquipos' => $ultimosEquipos,
            'puedeVerReportes' => $puedeVerReportes,
            'resumenComercial' => $resumenComercial,
            'indicadoresDecision' => $indicadoresDecision,
        ];
    }

    private function equiposBase(?User $usuario): Builder
    {
        $query = Equipo::query()
            ->where('activo', true);

        $this->aplicarRestriccionAlmacen($query, $usuario);

        return $query;
    }

    private function aplicarRestriccionAlmacen(
        Builder $query,
        ?User $usuario
    ): void {
        if (!$this->usuarioRestringidoPorAlmacen($usuario)) {
            return;
        }

        if ($usuario->almacen_operativo_id === null) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(
            'almacen_actual_id',
            $usuario->almacen_operativo_id
        );
    }

    private function usuarioRestringidoPorAlmacen(?User $usuario): bool
    {
        return $usuario !== null
            && !$usuario->esAdministradorGlobal();
    }
}
