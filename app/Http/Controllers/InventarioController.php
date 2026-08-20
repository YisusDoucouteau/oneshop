<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\TrazabilidadEquipoService;
class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        $busqueda = trim((string) $request->query('buscar', ''));
        $estadoId = $request->integer('estado');
        $almacenId = $request->integer('almacen');

        $equiposQuery = Equipo::query()
            ->with([
                'producto.marca',
                'almacenActual',
                'estadoActual',
                'condicionFisica',
                'precioVigente',
            ])
            ->where('activo', true);

        if ($busqueda !== '') {
            $equiposQuery->where(function ($query) use ($busqueda) {
                $query
                    ->where('codigo_interno', 'like', "%{$busqueda}%")
                    ->orWhere('serial_fabricante', 'like', "%{$busqueda}%")
                    ->orWhereHas('producto', function ($productoQuery) use ($busqueda) {
                        $productoQuery
                            ->where('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('modelo', 'like', "%{$busqueda}%");
                    });
            });
        }

        if ($estadoId > 0) {
            $equiposQuery->where(
                'estado_actual_id',
                $estadoId
            );
        }

        if ($almacenId > 0) {
            $equiposQuery->where(
                'almacen_actual_id',
                $almacenId
            );
        }

        $equipos = $equiposQuery
            ->orderByDesc('fecha_registro')
            ->paginate(15)
            ->withQueryString();

        $estados = EstadoEquipo::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $almacenes = Almacen::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $totalEquipos = Equipo::query()
            ->where('activo', true)
            ->count();

        $resumenEstados = EstadoEquipo::query()
            ->withCount([
                'equipos as cantidad' => function ($query) {
                    $query->where('activo', true);
                },
            ])
            ->where('activo', true)
            ->get();

        return view(
            'inventario.index',
            compact(
                'equipos',
                'estados',
                'almacenes',
                'totalEquipos',
                'resumenEstados',
                'busqueda',
                'estadoId',
                'almacenId'
            )
        );
    }
    public function show(
    Equipo $equipo,
    TrazabilidadEquipoService $trazabilidad
): View {
    $equipo->load([
        'producto.marca',
        'producto.categoria',

        'almacenActual',
        'estadoActual',
        'condicionFisica',

        'especificacion',
        'precioVigente',

        'detalleLote.lote.proveedor',
    ]);

    $eventosTrazabilidad = $trazabilidad->obtener(
        $equipo
    );

    return view(
        'inventario.show',
        compact(
            'equipo',
            'eventosTrazabilidad'
        )
    );
}
    }