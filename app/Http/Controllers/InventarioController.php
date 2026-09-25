<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\CondicionFisica;
use App\Models\Equipo;
use App\Models\EstadoEquipo;
use App\Models\Producto;
use App\Services\RegistroEquipoService;
use App\Models\MetodoPago;
use App\Services\AjusteGarantiaService;
use App\Services\TrazabilidadEquipoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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
    public function create(): View
{
    $productos = Producto::query()
        ->with([
            'marca',
            'categoria',
        ])
        ->where('activo', true)
        ->where('es_serializado', true)
        ->orderBy('nombre')
        ->orderBy('modelo')
        ->get();

    $almacenes = Almacen::query()
        ->where('activo', true)
        ->orderByDesc('principal')
        ->orderBy('nombre')
        ->get();

    $condiciones = CondicionFisica::query()
        ->where('activo', true)
        ->orderBy('codigo')
        ->get();

    return view(
        'inventario.create',
        compact(
            'productos',
            'almacenes',
            'condiciones'
        )
    );
}

public function store(
    Request $request,
    RegistroEquipoService $registroEquipo
): RedirectResponse {
    try {
        $equipo = $registroEquipo->registrar(
            $request->user()->id,
            $request->all()
        );

        return redirect()
            ->route(
                'inventario.show',
                $equipo->codigo_interno
            )
            ->with(
                'success',
                'Equipo registrado correctamente.'
            );

    } catch (\App\Exceptions\ReglaNegocioException $exception) {

        return back()
            ->withInput()
            ->withErrors([
                'registro' => $exception->getMessage(),
            ]);
    }
}
   public function show(
    Equipo $equipo,
    TrazabilidadEquipoService $trazabilidad,
    AjusteGarantiaService $ajusteGarantiaService
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

        'casosGarantia.recibidoPor',
        'casosGarantia.cerradoPor',
        'casosGarantia.intervenciones.usuario',
        'casosGarantia.cambioEquipo.equipoSaliente',
        'casosGarantia.cambioEquipo.equipoEntrante',
        'casosGarantia.cambioEquipo.autorizadoPor',
        'casosGarantia.cambioEquipo.movimientosAjuste.metodoPago',
        'casosGarantia.cambioEquipo.movimientosAjuste.registradoPor',
        'casosGarantia.cambioEquipo.movimientosAjuste.verificadoPor',

        'historialEstados.estadoOrigen',
        'historialEstados.estadoDestino',
        'historialEstados.usuario',
    ]);

    $eventosTrazabilidad =
        $trazabilidad->obtener(
            $equipo
        );

    $equiposReemplazo =
        collect();

    if (
        auth()->user()?->tienePermiso(
            'garantias.autorizar_cambio'
        )
    ) {
        $equiposReemplazo =
            Equipo::query()
                ->with([
                    'almacenActual',
                    'producto.marca',
                    'estadoActual',
                    'precioVigente',
                ])

                /*
                 * Nunca puede seleccionarse
                 * el mismo equipo original.
                 */
                ->where(
                    'id',
                    '<>',
                    $equipo->id
                )

                /*
                 * El reemplazo debe estar activo.
                 */
                ->where(
                    'activo',
                    true
                )

                /*
                 * Debe estar físicamente
                 * asociado a un almacén.
                 */
                ->whereNotNull(
                    'almacen_actual_id'
                )

                /*
                 * Solo equipos DISPONIBLES.
                 */
                ->whereHas(
                    'estadoActual',
                    function ($query) {
                        $query->where(
                            'codigo',
                            'DISPONIBLE'
                        );
                    }
                )

                /*
                 * No mostrar equipos que tengan
                 * una reserva activa.
                 */
                ->whereDoesntHave(
                    'detallesReservas.reserva',
                    function ($query) {
                        $query->where(
                            'estado',
                            'ACTIVA'
                        );
                    }
                )

                /*
                 * El producto del equipo debe tener
                 * stock disponible en su propio almacén.
                 */
                ->whereExists(
                    function ($query) {
                        $query
                            ->selectRaw('1')
                            ->from(
                                'existencias_productos'
                            )
                            ->whereColumn(
                                'existencias_productos.producto_id',
                                'equipos.producto_id'
                            )
                            ->whereColumn(
                                'existencias_productos.almacen_id',
                                'equipos.almacen_actual_id'
                            )
                            ->where(
                                'existencias_productos.cantidad_disponible',
                                '>',
                                0
                            );
                    }
                )

                ->orderBy(
                    'codigo_interno'
                )
                ->get();
    }


    $metodosPagoAjuste =
        collect();

    if (
        auth()->user()?->tienePermiso(
            'garantias.ajustes.registrar'
        )
    ) {
        $metodosPagoAjuste =
            MetodoPago::query()
                ->where(
                    'activo',
                    true
                )
                ->orderBy(
                    'nombre'
                )
                ->get();
    }

    $resumenesAjusteGarantia =
        collect();

    foreach (
        $equipo->casosGarantia
        as $caso
    ) {
        if (!$caso->cambioEquipo) {
            continue;
        }

        $resumenesAjusteGarantia->put(
            $caso->cambioEquipo->id,
            $ajusteGarantiaService
                ->obtenerResumen(
                    $caso->cambioEquipo->id
                )
        );
    }

return view(
        'inventario.show',
        compact(
            'equipo',
            'eventosTrazabilidad',
            'equiposReemplazo',
            'metodosPagoAjuste',
            'resumenesAjusteGarantia'
        )
    );
}
    }