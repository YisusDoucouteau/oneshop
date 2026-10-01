<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Almacen;
use App\Models\CategoriaProducto;
use App\Models\Marca;
use App\Models\Moneda;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\RegularizacionValoracionInventario;
use App\Services\CompraComponenteStockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComponenteInventarioController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();

        $almacenes = Almacen::query()
            ->where('activo', true)
            ->orderByDesc('principal')
            ->orderBy('nombre')
            ->get();

        $almacenId = $request->integer('almacen');

        if ($almacenId <= 0) {
            $almacenId = (int) (
                $usuario->almacen_operativo_id
                ?? $almacenes->first()?->id
                ?? 0
            );
        }

        $almacenSeleccionado = $almacenes
            ->firstWhere('id', $almacenId);

        if (!$almacenSeleccionado) {
            abort(404);
        }

        $busqueda = trim(
            (string) $request->query('buscar', '')
        );

        $estado = strtoupper(
            trim(
                (string) $request->query(
                    'estado',
                    ''
                )
            )
        );

        $estadosPermitidos = [
            '',
            'CON_STOCK',
            'SIN_VALORAR',
            'AGOTADO',
        ];

        if (!in_array(
            $estado,
            $estadosPermitidos,
            true
        )) {
            $estado = '';
        }

        $componentesQuery = Producto::query()
            ->with([
                'marca',
                'categoria',
                'almacenes' => function ($query) use ($almacenId) {
                    $query->where(
                        'almacenes.id',
                        $almacenId
                    );
                },
            ])
            ->where('activo', true)
            ->where('es_serializado', false)
            ->whereHas(
                'almacenes',
                function (Builder $query) use ($almacenId) {
                    $query->where(
                        'almacenes.id',
                        $almacenId
                    );
                }
            );

        if ($busqueda !== '') {
            $componentesQuery->where(
                function (Builder $query) use ($busqueda) {
                    $query
                        ->where(
                            'codigo',
                            'like',
                            "%{$busqueda}%"
                        )
                        ->orWhere(
                            'nombre',
                            'like',
                            "%{$busqueda}%"
                        )
                        ->orWhere(
                            'modelo',
                            'like',
                            "%{$busqueda}%"
                        )
                        ->orWhereHas(
                            'marca',
                            function (Builder $marcaQuery) use ($busqueda) {
                                $marcaQuery->where(
                                    'nombre',
                                    'like',
                                    "%{$busqueda}%"
                                );
                            }
                        );
                }
            );
        }

        if ($estado === 'CON_STOCK') {
            $componentesQuery->whereHas(
                'almacenes',
                function (Builder $query) use ($almacenId) {
                    $query
                        ->where(
                            'almacenes.id',
                            $almacenId
                        )
                        ->whereRaw(
                            '(existencias_productos.cantidad_disponible + existencias_productos.cantidad_reservada) > 0'
                        );
                }
            );
        }

        if ($estado === 'SIN_VALORAR') {
            $componentesQuery->whereHas(
                'almacenes',
                function (Builder $query) use ($almacenId) {
                    $query
                        ->where(
                            'almacenes.id',
                            $almacenId
                        )
                        ->whereNull(
                            'existencias_productos.costo_promedio_bob'
                        )
                        ->whereRaw(
                            '(existencias_productos.cantidad_disponible + existencias_productos.cantidad_reservada) > 0'
                        );
                }
            );
        }

        if ($estado === 'AGOTADO') {
            $componentesQuery->whereHas(
                'almacenes',
                function (Builder $query) use ($almacenId) {
                    $query
                        ->where(
                            'almacenes.id',
                            $almacenId
                        )
                        ->whereRaw(
                            '(existencias_productos.cantidad_disponible + existencias_productos.cantidad_reservada) = 0'
                        );
                }
            );
        }

        $componentes = $componentesQuery
            ->orderBy('nombre')
            ->orderBy('modelo')
            ->paginate(15)
            ->withQueryString();

        $resumen = DB::table(
            'existencias_productos as e'
        )
            ->join(
                'productos as p',
                'p.id',
                '=',
                'e.producto_id'
            )
            ->where(
                'e.almacen_id',
                $almacenId
            )
            ->where(
                'p.activo',
                true
            )
            ->where(
                'p.es_serializado',
                false
            )
            ->selectRaw(
                'COUNT(*) as componentes'
            )
            ->selectRaw(
                'COALESCE(SUM(e.cantidad_disponible), 0) as disponibles'
            )
            ->selectRaw(
                'COALESCE(SUM(e.cantidad_reservada), 0) as reservadas'
            )
            ->selectRaw(
                'COALESCE(SUM(
                    CASE
                        WHEN e.costo_promedio_bob IS NOT NULL
                        THEN
                            (e.cantidad_disponible + e.cantidad_reservada)
                            * e.costo_promedio_bob
                        ELSE 0
                    END
                ), 0) as valor_stock_bob'
            )
            ->selectRaw(
                'COALESCE(SUM(
                    CASE
                        WHEN e.costo_promedio_bob IS NULL
                        AND (e.cantidad_disponible + e.cantidad_reservada) > 0
                        THEN 1
                        ELSE 0
                    END
                ), 0) as sin_valorar'
            )
            ->first();

        $productosCompra = Producto::query()
            ->with('marca')
            ->where('activo', true)
            ->where('es_serializado', false)
            ->orderBy('nombre')
            ->orderBy('modelo')
            ->get();

        $monedas = Moneda::query()
            ->where('activo', true)
            ->whereIn(
                'codigo',
                [
                    'BOB',
                    'USD',
                    'USDT',
                ]
            )
            ->orderByRaw(
                "CASE codigo
                    WHEN 'BOB' THEN 1
                    WHEN 'USD' THEN 2
                    WHEN 'USDT' THEN 3
                    ELSE 4
                END"
            )
            ->get();

        $almacenesCompra = $usuario->almacen_operativo_id
            ? $almacenes->where(
                'id',
                (int) $usuario->almacen_operativo_id
            )->values()
            : $almacenes;

        $categorias = CategoriaProducto::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $categoriaComponenteId =
            $categorias
                ->firstWhere(
                    'codigo',
                    'COMPONENTE'
                )
                ?->id;

        $marcas = Marca::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $movimientosRecientes = MovimientoInventario::query()
            ->with([
                'producto.marca',
                'tipoMovimiento',
                'usuario',
                'moneda',
                'tipoCambio',
            ])
            ->where(
                'almacen_id',
                $almacenId
            )
            ->whereHas(
                'producto',
                function (Builder $query) {
                    $query
                        ->where(
                            'activo',
                            true
                        )
                        ->where(
                            'es_serializado',
                            false
                        );
                }
            )
            ->latest(
                'fecha_movimiento'
            )
            ->limit(12)
            ->get();

        $regularizacionesRecientes =
            RegularizacionValoracionInventario::query()
                ->with([
                    'producto.marca',
                    'usuario',
                ])
                ->where(
                    'almacen_id',
                    $almacenId
                )
                ->latest(
                    'fecha_regularizacion'
                )
                ->limit(10)
                ->get();

        return view(
            'inventario.componentes.index',
            compact(
                'componentes',
                'almacenes',
                'almacenSeleccionado',
                'productosCompra',
                'monedas',
                'almacenesCompra',
                'categorias',
                'categoriaComponenteId',
                'marcas',
                'movimientosRecientes',
                'regularizacionesRecientes',
                'resumen',
                'busqueda',
                'estado'
            )
        );
    }

    public function storeComponente(
        Request $request
    ): RedirectResponse {
        $request->merge([
            'nombre' => trim(
                (string) $request->input(
                    'nombre'
                )
            ),
        ]);

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:180',
            ],

            'categoria_producto_id' => [
                'required',
                'integer',
                'exists:categorias_productos,id',
            ],

            'marca_id' => [
                'nullable',
                'integer',
                'exists:marcas,id',
            ],

            'modelo' => [
                'nullable',
                'string',
                'max:150',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'almacen_contexto' => [
                'nullable',
                'integer',
                'exists:almacenes,id',
            ],
        ]);

        $codigo =
            $this->generarCodigoComponente(
                $datos['nombre'],
                $datos['modelo'] ?? null
            );

        $producto = Producto::query()->create([
            'categoria_producto_id' =>
                $datos['categoria_producto_id'],

            'marca_id' =>
                $datos['marca_id'] ?? null,

            'codigo' =>
                $codigo,

            'nombre' =>
                $datos['nombre'],

            'modelo' =>
                $datos['modelo'] ?? null,

            'descripcion' =>
                $datos['descripcion'] ?? null,

            'es_serializado' =>
                false,

            'activo' =>
                true,
        ]);

        return redirect()
            ->route(
                'inventario.componentes.index',
                array_filter([
                    'almacen' =>
                        $datos['almacen_contexto']
                        ?? null,

                    'compra' =>
                        1,

                    'producto_id' =>
                        $producto->id,
                ])
            )
            ->with(
                'success',
                "Componente {$producto->nombre} registrado con código {$producto->codigo}. Completa ahora la compra para incorporarlo al stock."
            );
    }

    public function storeCompra(
        Request $request,
        CompraComponenteStockService $service
    ): RedirectResponse {
        try {
            $movimiento = $service->registrarCompra(
                $request->user()->id,
                $request->all()
            );

            return redirect()
                ->route(
                    'inventario.componentes.index',
                    [
                        'almacen' =>
                            $movimiento->almacen_id,
                    ]
                )
                ->with(
                    'success',
                    'Compra de componente registrada correctamente. '
                    .'Nuevo saldo disponible: '
                    .$movimiento
                        ->saldo_disponible_resultante
                    .'.'
                );
        } catch (
            ReglaNegocioException $exception
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'compra_componente' =>
                        $exception->getMessage(),
                ]);
        }
    }
    private function generarCodigoComponente(
        string $nombre,
        ?string $modelo = null
    ): string {
        $partes = [
            'CMP',
            $nombre,
        ];

        if (
            $modelo !== null
            && trim($modelo) !== ''
            && !str_contains(
                mb_strtoupper($nombre),
                mb_strtoupper(
                    trim($modelo)
                )
            )
        ) {
            $partes[] = trim($modelo);
        }

        $base = Str::upper(
            Str::ascii(
                implode(
                    '-',
                    $partes
                )
            )
        );

        $base = preg_replace(
            '/[^A-Z0-9]+/',
            '-',
            $base
        ) ?? 'CMP';

        $base = trim(
            $base,
            '-'
        );

        $base = substr(
            $base,
            0,
            64
        );

        $correlativo = 1;

        do {
            $codigo =
                $base
                .'-'
                .str_pad(
                    (string) $correlativo,
                    3,
                    '0',
                    STR_PAD_LEFT
                );

            $correlativo++;
        } while (
            Producto::query()
                ->where(
                    'codigo',
                    $codigo
                )
                ->exists()
        );

        return $codigo;
    }

}
