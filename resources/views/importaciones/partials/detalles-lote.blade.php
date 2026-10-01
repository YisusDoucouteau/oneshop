@php
    $totalTiposProducto = $lote->detalles->count();
    $totalEsperadasComposicion = $lote->detalles->sum(
        fn ($detalle) => (int) $detalle->cantidad_esperada
    );
    $puedeGestionarComposicion = $puedeGestionar
        ?? auth()->user()->tienePermiso('importacion.gestionar');

    $datosDetallesComposicion = $lote->detalles->mapWithKeys(function ($detalle) {
        $unidadesActivas = $detalle->unidadesAdquiridas
            ->where('estado', '!=', \App\Models\UnidadAdquirida::ESTADO_ANULADA)
            ->count();

        $especificacion = $detalle->especificacionEsperada;

        return [
            $detalle->id => [
                'id' => $detalle->id,
                'producto' => trim(collect([
                    $detalle->producto?->marca?->nombre,
                    $detalle->producto?->nombre,
                ])->filter()->implode(' ')),
                'modelo' => $detalle->producto?->modelo,
                'cantidad_esperada' => (int) $detalle->cantidad_esperada,
                'cantidad_recibida' => $unidadesActivas,
                'tiene_historial' => $detalle->unidadesAdquiridas->isNotEmpty(),
                'moneda_id' => $detalle->moneda_id,
                'moneda_codigo' => $detalle->moneda?->codigo,
                'costo_unitario_origen' => $detalle->costo_unitario_origen,
                'tipo_cambio_aplicado' => $detalle->tipoCambioCompra?->valor,
                'observacion' => $detalle->observacion,
                'especificacion' => [
                    'procesador' => $especificacion?->procesador,
                    'generacion_procesador' => $especificacion?->generacion_procesador,
                    'ram_gb' => $especificacion?->ram_gb,
                    'almacenamiento_gb' => $especificacion?->almacenamiento_gb,
                    'tipo_almacenamiento' => $especificacion?->tipo_almacenamiento,
                    'tarjeta_grafica' => $especificacion?->tarjeta_grafica,
                    'pantalla_pulgadas' => $especificacion?->pantalla_pulgadas,
                    'resolucion' => $especificacion?->resolucion,
                    'sistema_operativo' => $especificacion?->sistema_operativo,
                ],
                'componentes' => $detalle->componentesEsperados->map(fn ($componente) => [
                    'nombre' => $componente->nombre,
                    'cantidad_por_unidad' => (int) $componente->cantidad_por_unidad,
                    'incluido_en_compra' => (bool) $componente->incluido_en_compra,
                    'observacion' => $componente->observacion,
                ])->values()->all(),
            ],
        ];
    });
@endphp

<section id="composicion" class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                <x-ui.icon name="package" size="18" />
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">Composición</p>
                <h2 class="mt-0.5 text-lg font-bold text-slate-950">Productos del lote</h2>
                <p class="mt-1 text-sm text-slate-500">Productos y cantidades esperadas en esta importación.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">
                {{ $totalTiposProducto }} {{ $totalTiposProducto === 1 ? 'producto' : 'productos' }}
            </span>
            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-oneshop-dark">
                {{ $totalEsperadasComposicion }} {{ $totalEsperadasComposicion === 1 ? 'unidad' : 'unidades' }}
            </span>
        </div>
    </div>

    <div class="p-6">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="relative w-full lg:max-w-md">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-ui.icon name="search" size="16" />
                </div>
                <input
                    type="search"
                    id="buscarComposicionLote"
                    placeholder="Buscar por producto, marca o modelo..."
                    class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                >
            </div>

            <div class="flex items-center justify-between gap-3 sm:justify-end">
                <span id="contadorComposicion" class="text-xs font-semibold text-slate-500">0 de 0</span>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        id="paginaAnteriorComposicion"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="Página anterior"
                    >
                        <span aria-hidden="true" class="text-lg font-bold leading-none">‹</span>
                    </button>
                    <button
                        type="button"
                        id="paginaSiguienteComposicion"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="Página siguiente"
                    >
                        <span aria-hidden="true" class="text-lg font-bold leading-none">›</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Producto</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Esperadas</th>
                            <th class="px-5 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-slate-500">Recibidas</th>
                            <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Estado</th>
                            @if($puedeGestionarComposicion)
                                <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Acción</th>
                            @endif
                        </tr>
                    </thead>

                    <tbody id="tablaComposicionLote" class="divide-y divide-slate-100 bg-white">
                        @forelse($lote->detalles as $detalle)
                            @php
                                $recibidas = $detalle->unidadesAdquiridas
                                    ->where('estado', '!=', \App\Models\UnidadAdquirida::ESTADO_ANULADA)
                                    ->count();
                                $pendientes = max(0, (int) $detalle->cantidad_esperada - $recibidas);
                                $tieneHistorial = $detalle->unidadesAdquiridas->isNotEmpty();
                                $textoBusqueda = strtolower(trim(collect([
                                    $detalle->producto?->marca?->nombre,
                                    $detalle->producto?->nombre,
                                    $detalle->producto?->modelo,
                                ])->filter()->implode(' ')));
                            @endphp

                            <tr
                                data-composicion-row
                                data-search="{{ $textoBusqueda }}"
                                class="transition hover:bg-blue-50/30"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-oneshop-primary">
                                            <x-ui.icon name="package" size="16" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900">
                                                {{ $detalle->producto?->marca?->nombre }} {{ $detalle->producto?->nombre }}
                                            </p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ $detalle->producto?->modelo ?? 'Sin modelo' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-5 py-4 text-center font-bold text-slate-800">{{ $detalle->cantidad_esperada }}</td>
                                <td class="px-5 py-4 text-center font-bold text-slate-800">{{ $recibidas }}</td>

                                <td class="px-5 py-4 text-right">
                                    @if($pendientes > 0)
                                        <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">
                                            {{ $pendientes }} pendiente{{ $pendientes === 1 ? '' : 's' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                                            <x-ui.icon name="check" size="12" />
                                            Completo
                                        </span>
                                    @endif
                                </td>

                                @if($puedeGestionarComposicion)
                                    <td class="px-5 py-4 text-right align-top">
                                        <details class="ml-auto w-48 rounded-lg border border-slate-200 bg-white text-left shadow-sm">
                                            <summary class="cursor-pointer list-none px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                                Gestionar ▾
                                            </summary>
                                            <div class="border-t border-slate-100 p-1.5">
                                                <button
                                                    type="button"
                                                    onclick="abrirModalEditarDetalleLote({{ $detalle->id }})"
                                                    class="w-full rounded-md px-2.5 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-oneshop-dark"
                                                >
                                                    Editar datos del lote
                                                </button>

                                                <button
                                                    type="button"
                                                    onclick="abrirModalEspecificacionDetalleLote({{ $detalle->id }})"
                                                    class="w-full rounded-md px-2.5 py-2 text-left text-xs font-semibold text-slate-700 hover:bg-blue-50 hover:text-oneshop-dark"
                                                >
                                                    Ver especificación esperada
                                                </button>

                                                @if(!$tieneHistorial)
                                                    <button
                                                        type="button"
                                                        onclick="eliminarDetalleLote({{ $detalle->id }})"
                                                        class="mt-1 w-full rounded-md px-2.5 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50"
                                                    >
                                                        Quitar del lote
                                                    </button>
                                                @else
                                                    <p class="mt-1 rounded-md bg-slate-50 px-2.5 py-2 text-[11px] leading-4 text-slate-500">
                                                        No se puede quitar porque ya existe historial de recepción.
                                                    </p>
                                                @endif
                                            </div>
                                        </details>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr id="filaVaciaComposicion">
                                <td colspan="{{ $puedeGestionarComposicion ? 5 : 4 }}" class="px-6 py-12 text-center">
                                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                        <x-ui.icon name="package" size="20" />
                                    </div>
                                    <p class="mt-3 font-bold text-slate-700">Sin productos registrados</p>
                                    <p class="mt-1 text-sm text-slate-500">La composición del lote todavía está vacía.</p>
                                </td>
                            </tr>
                        @endforelse

                        <tr id="sinResultadosComposicion" class="hidden">
                            <td colspan="{{ $puedeGestionarComposicion ? 5 : 4 }}" class="px-6 py-10 text-center text-sm text-slate-500">
                                No se encontraron productos con esa búsqueda.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

@if($puedeGestionarComposicion)
    {{-- MODAL EDITAR DATOS DEL LOTE --}}
    <div id="modalEditarDetalleLote" class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm" onclick="cerrarModalEditarDetalleLoteDesdeFondo(event)">
        <div class="flex min-h-full items-center justify-center">
            <div class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">Composición del lote</p>
                        <h2 class="mt-0.5 text-xl font-bold text-slate-950">Editar datos del lote</h2>
                        <p id="editarDetalleProducto" class="mt-1 text-sm text-slate-500"></p>
                    </div>
                    <button type="button" onclick="cerrarModalEditarDetalleLote()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-blue-100 hover:text-slate-900">
                        <x-ui.icon name="x" size="17" />
                    </button>
                </div>

                <form id="formEditarDetalleLote" class="flex min-h-0 flex-1 flex-col">
                    @csrf
                    <div class="min-h-0 flex-1 overflow-y-auto p-6">
                        <div id="errorEditarDetalleLote" class="mb-5 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"></div>

                        <div id="avisoDetalleBloqueado" class="mb-5 hidden rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            La recepción de esta línea ya comenzó. Para preservar la trazabilidad solo puede corregirse la cantidad esperada.
                        </div>

                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12">
                            <div class="xl:col-span-3">
                                <label class="mb-2 block text-sm font-bold text-slate-700">Cantidad esperada *</label>
                                <input type="number" id="editarCantidadEsperada" name="cantidad_esperada" min="1" required class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                            </div>

                            <div class="campo-edicion-completa xl:col-span-3">
                                <label class="mb-2 block text-sm font-bold text-slate-700">Costo unitario</label>
                                <input type="number" id="editarCostoUnitario" name="costo_unitario_origen" min="0" step="0.01" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                            </div>

                            <div class="campo-edicion-completa xl:col-span-3">
                                <label class="mb-2 block text-sm font-bold text-slate-700">Moneda</label>
                                <select id="editarMonedaDetalle" name="moneda_id" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                                    <option value="">Seleccionar</option>
                                    @foreach($monedas as $moneda)
                                        <option value="{{ $moneda->id }}" data-codigo="{{ $moneda->codigo }}">{{ $moneda->codigo }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="campo-edicion-completa xl:col-span-3">
                                <label class="mb-2 block text-sm font-bold text-slate-700">Observación</label>
                                <input type="text" id="editarObservacionDetalle" name="observacion" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                            </div>
                        </div>

                        <div id="grupoTipoCambioEditar" class="campo-edicion-completa mt-4 hidden rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">Referencia cambiaria</p>
                                    @if($referenciaUsdBob)
                                        <div class="mt-2 flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">USD / BOB</p>
                                                <p class="mt-0.5 text-xs text-slate-500">Referencia disponible por OneShop.</p>
                                            </div>
                                            <span class="text-lg font-bold text-oneshop-dark">{{ $referenciaUsdBob['tipo_cambio']->valor }}</span>
                                        </div>
                                        <button type="button" id="usarReferenciaEditar" class="mt-3 rounded-lg border border-blue-200 bg-white px-3 py-2 text-xs font-bold text-oneshop-dark hover:bg-blue-50">Usar referencia</button>
                                    @else
                                        <p class="mt-2 text-sm text-slate-500">No hay una referencia USD/BOB disponible en este momento.</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700">Tipo de cambio aplicado *</label>
                                    <input type="number" id="editarTipoCambioDetalle" name="tipo_cambio_aplicado" min="0.0001" step="0.0001" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100">
                                    <p id="notaTipoCambioEditar" class="mt-2 text-xs text-slate-500"></p>
                                </div>
                            </div>
                        </div>

                        <div class="campo-edicion-completa mt-6 rounded-xl border border-slate-200">
                            <div class="border-b border-slate-200 px-5 py-4">
                                <p class="text-sm font-bold text-slate-800">Características y accesorios esperados</p>
                                <p class="mt-0.5 text-xs text-slate-500">Información declarada para esta línea de compra.</p>
                            </div>

                            <div class="p-5">
                                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    @foreach([
                                        ['procesador', 'Procesador'],
                                        ['generacion_procesador', 'Generación'],
                                        ['tarjeta_grafica', 'Tarjeta gráfica'],
                                        ['resolucion', 'Resolución'],
                                        ['sistema_operativo', 'Sistema operativo'],
                                    ] as [$campo, $label])
                                        <input
                                            name="especificacion_esperada[{{ $campo }}]"
                                            data-editar-especificacion="{{ $campo }}"
                                            placeholder="{{ $label }}"
                                            class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                        >
                                    @endforeach

                                    <input type="number" min="0" name="especificacion_esperada[ram_gb]" data-editar-especificacion="ram_gb" placeholder="RAM (GB)" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                    <input type="number" min="0" name="especificacion_esperada[almacenamiento_gb]" data-editar-especificacion="almacenamiento_gb" placeholder="Almacenamiento (GB)" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                    <select name="especificacion_esperada[tipo_almacenamiento]" data-editar-especificacion="tipo_almacenamiento" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                        <option value="">Tipo de almacenamiento</option>
                                        <option value="SSD">SSD</option>
                                        <option value="HDD">HDD</option>
                                        <option value="NVME">NVMe</option>
                                        <option value="EMMC">eMMC</option>
                                    </select>
                                    <input type="number" min="0" step="0.1" name="especificacion_esperada[pantalla_pulgadas]" data-editar-especificacion="pantalla_pulgadas" placeholder="Pantalla (pulgadas)" class="rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm">
                                </div>

                                <div class="mt-5 border-t border-slate-200 pt-4">
                                    <div class="flex items-center justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">Accesorios por equipo</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Ej. cargador, adaptador o cable.</p>
                                        </div>
                                        <button type="button" id="agregarComponenteEditar" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-oneshop-dark hover:bg-blue-100">+ Agregar accesorio</button>
                                    </div>
                                    <div id="componentesEsperadosEditar" class="mt-3 space-y-3"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-blue-100 bg-blue-50/40 px-6 py-4 sm:flex-row sm:justify-end">
                        <button type="button" onclick="cerrarModalEditarDetalleLote()" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancelar</button>
                        <button type="submit" id="btnGuardarEdicionDetalle" class="rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL VER ESPECIFICACIÓN --}}
    <div id="modalEspecificacionDetalleLote" class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm" onclick="cerrarModalEspecificacionDetalleLoteDesdeFondo(event)">
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">Composición del lote</p>
                        <h2 class="mt-0.5 text-xl font-bold text-slate-950">Especificación esperada</h2>
                        <p id="tituloEspecificacionDetalle" class="mt-1 text-sm text-slate-500"></p>
                    </div>
                    <button type="button" onclick="cerrarModalEspecificacionDetalleLote()" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-blue-100 hover:text-slate-900">
                        <x-ui.icon name="x" size="17" />
                    </button>
                </div>
                <div id="contenidoEspecificacionDetalle" class="max-h-[70vh] overflow-y-auto p-6"></div>
                <div class="flex justify-end border-t border-slate-200 bg-slate-50 px-6 py-4">
                    <button type="button" onclick="cerrarModalEspecificacionDetalleLote()" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL CONFIRMAR ELIMINACIÓN --}}
    <div
        id="modalEliminarDetalleLote"
        class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm"
        onclick="cerrarModalEliminarDetalleLoteDesdeFondo(event)"
    >
        <div class="flex min-h-full items-center justify-center">
            <div class="w-full max-w-md overflow-hidden rounded-2xl border border-red-100 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-white px-6 py-5">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-red-200 bg-white text-lg font-black text-red-700 shadow-sm">
                            !
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-red-700">Composición del lote</p>
                            <h2 class="mt-0.5 text-xl font-bold text-slate-950">Quitar producto del lote</h2>
                            <p id="eliminarDetalleProducto" class="mt-1 text-sm text-slate-500"></p>
                        </div>
                    </div>

                    <button
                        type="button"
                        onclick="cerrarModalEliminarDetalleLote()"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-red-100 hover:text-slate-900"
                        aria-label="Cerrar"
                    >
                        <x-ui.icon name="x" size="17" />
                    </button>
                </div>

                <div class="p-6">
                    <p class="text-sm leading-6 text-slate-600">
                        Esta línea se eliminará de la composición del lote. Solo puede quitarse mientras no exista historial de recepción asociado.
                    </p>

                    <div
                        id="errorEliminarDetalleLote"
                        class="mt-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800"
                    ></div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="cerrarModalEliminarDetalleLote()"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        id="btnConfirmarEliminarDetalle"
                        class="rounded-xl border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-bold text-red-800 shadow-sm transition hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Quitar del lote
                    </button>
                </div>
            </div>
        </div>
    </div>

@endif

@push('scripts')
<script>
(function () {
    const detalles = @json($datosDetallesComposicion);
    const filas = Array.from(document.querySelectorAll('[data-composicion-row]'));
    const buscador = document.getElementById('buscarComposicionLote');
    const contador = document.getElementById('contadorComposicion');
    const botonAnterior = document.getElementById('paginaAnteriorComposicion');
    const botonSiguiente = document.getElementById('paginaSiguienteComposicion');
    const sinResultados = document.getElementById('sinResultadosComposicion');
    const paginaTamano = 10;
    let paginaActual = 1;

    function normalizar(valor) {
        return (valor ?? '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function renderizarComposicion() {
        if (!contador || !botonAnterior || !botonSiguiente) {
            return;
        }

        const termino = normalizar(buscador?.value);
        const filtradas = filas.filter((fila) => normalizar(fila.dataset.search).includes(termino));
        const paginas = Math.max(1, Math.ceil(filtradas.length / paginaTamano));
        paginaActual = Math.min(paginaActual, paginas);

        filas.forEach((fila) => fila.classList.add('hidden'));

        const inicio = (paginaActual - 1) * paginaTamano;
        const visibles = filtradas.slice(inicio, inicio + paginaTamano);
        visibles.forEach((fila) => fila.classList.remove('hidden'));

        const desde = filtradas.length === 0 ? 0 : inicio + 1;
        const hasta = filtradas.length === 0 ? 0 : inicio + visibles.length;
        contador.textContent = filtradas.length === 0
            ? '0 de 0'
            : `${desde}–${hasta} de ${filtradas.length}`;

        botonAnterior.disabled = paginaActual <= 1 || filtradas.length === 0;
        botonSiguiente.disabled = paginaActual >= paginas || filtradas.length === 0;
        sinResultados?.classList.toggle('hidden', filtradas.length !== 0 || filas.length === 0);
    }

    buscador?.addEventListener('input', () => {
        paginaActual = 1;
        renderizarComposicion();
    });

    botonAnterior?.addEventListener('click', () => {
        if (paginaActual > 1) {
            paginaActual--;
            renderizarComposicion();
        }
    });

    botonSiguiente?.addEventListener('click', () => {
        paginaActual++;
        renderizarComposicion();
    });

    renderizarComposicion();

    @if($puedeGestionarComposicion)
        const modalEditar = document.getElementById('modalEditarDetalleLote');
        const formEditar = document.getElementById('formEditarDetalleLote');
        const errorEditar = document.getElementById('errorEditarDetalleLote');
        const avisoBloqueado = document.getElementById('avisoDetalleBloqueado');
        const productoEditar = document.getElementById('editarDetalleProducto');
        const cantidadEditar = document.getElementById('editarCantidadEsperada');
        const costoEditar = document.getElementById('editarCostoUnitario');
        const monedaEditar = document.getElementById('editarMonedaDetalle');
        const observacionEditar = document.getElementById('editarObservacionDetalle');
        const grupoTipoCambioEditar = document.getElementById('grupoTipoCambioEditar');
        const tipoCambioEditar = document.getElementById('editarTipoCambioDetalle');
        const usarReferenciaEditar = document.getElementById('usarReferenciaEditar');
        const notaTipoCambioEditar = document.getElementById('notaTipoCambioEditar');
        const componentesEditar = document.getElementById('componentesEsperadosEditar');
        const botonGuardarEditar = document.getElementById('btnGuardarEdicionDetalle');
        const modalEliminar = document.getElementById('modalEliminarDetalleLote');
        const eliminarDetalleProducto = document.getElementById('eliminarDetalleProducto');
        const errorEliminar = document.getElementById('errorEliminarDetalleLote');
        const botonConfirmarEliminar = document.getElementById('btnConfirmarEliminarDetalle');
        const endpointBase = @js(route('importaciones.detalles.store', $lote));
        let detalleEditandoId = null;
        let detalleEliminarId = null;
        let indiceComponenteEditar = 0;

        function mostrarErrorEditar(mensaje) {
            errorEditar.textContent = mensaje;
            errorEditar.classList.remove('hidden');
        }

        function limpiarErrorEditar() {
            errorEditar.textContent = '';
            errorEditar.classList.add('hidden');
        }

        function actualizarTipoCambioEditar() {
            const codigo = monedaEditar?.options[monedaEditar.selectedIndex]?.dataset.codigo;
            const requiere = codigo === 'USD' || codigo === 'USDT';
            grupoTipoCambioEditar?.classList.toggle('hidden', !requiere);
            if (tipoCambioEditar) {
                tipoCambioEditar.required = requiere;
            }
            if (usarReferenciaEditar) {
                usarReferenciaEditar.classList.toggle('hidden', codigo !== 'USD');
            }
            if (notaTipoCambioEditar) {
                notaTipoCambioEditar.textContent = codigo === 'USDT'
                    ? 'La referencia USD/BOB es solo informativa. Confirma el tipo de cambio realmente aplicado a USDT.'
                    : 'Guarda el tipo de cambio que realmente se aplicó a esta compra.';
            }
            if (!requiere && tipoCambioEditar) {
                tipoCambioEditar.value = '';
            }
        }

        function agregarComponenteEditar(componente = {}) {
            const indice = indiceComponenteEditar++;
            const fila = document.createElement('div');
            fila.className = 'grid gap-3 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-[2fr_100px_2fr_auto]';
            fila.innerHTML = `
                <input name="componentes_esperados[${indice}][nombre]" value="${escaparHtml(componente.nombre ?? '')}" placeholder="Ej. cargador" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
                <input type="number" min="1" value="${Number(componente.cantidad_por_unidad ?? 1)}" name="componentes_esperados[${indice}][cantidad_por_unidad]" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm" required>
                <input name="componentes_esperados[${indice}][observacion]" value="${escaparHtml(componente.observacion ?? '')}" placeholder="Observación opcional" class="rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                <button type="button" class="quitar-componente-editar rounded-lg px-3 py-2 text-sm font-bold text-red-700 hover:bg-red-50">Quitar</button>
                <input type="hidden" name="componentes_esperados[${indice}][incluido_en_compra]" value="${componente.incluido_en_compra === false ? 0 : 1}">
            `;
            fila.querySelector('.quitar-componente-editar')?.addEventListener('click', () => fila.remove());
            componentesEditar?.appendChild(fila);
        }

        function bloquearCamposCompletos(bloquear) {
            document.querySelectorAll('.campo-edicion-completa input, .campo-edicion-completa select, .campo-edicion-completa textarea, .campo-edicion-completa button')
                .forEach((campo) => {
                    if (campo.id !== 'btnGuardarEdicionDetalle') {
                        campo.disabled = bloquear;
                    }
                });
            avisoBloqueado?.classList.toggle('hidden', !bloquear);
        }

        window.abrirModalEditarDetalleLote = function (id) {
            const detalle = detalles[id];
            if (!detalle || !modalEditar || !formEditar) {
                return;
            }

            detalleEditandoId = id;
            formEditar.reset();
            limpiarErrorEditar();
            productoEditar.textContent = `${detalle.producto || 'Producto'}${detalle.modelo ? ' · ' + detalle.modelo : ''}`;
            cantidadEditar.value = detalle.cantidad_esperada;
            cantidadEditar.min = Math.max(1, Number(detalle.cantidad_recibida || 0));
            costoEditar.value = detalle.costo_unitario_origen ?? '';
            monedaEditar.value = detalle.moneda_id ?? '';
            observacionEditar.value = detalle.observacion ?? '';
            tipoCambioEditar.value = detalle.tipo_cambio_aplicado ?? '';

            document.querySelectorAll('[data-editar-especificacion]').forEach((campo) => {
                campo.value = detalle.especificacion?.[campo.dataset.editarEspecificacion] ?? '';
            });

            componentesEditar.innerHTML = '';
            indiceComponenteEditar = 0;
            (detalle.componentes || []).forEach(agregarComponenteEditar);

            bloquearCamposCompletos(Boolean(detalle.tiene_historial));
            actualizarTipoCambioEditar();
            modalEditar.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
            window.setTimeout(() => cantidadEditar?.focus(), 80);
        };

        window.cerrarModalEditarDetalleLote = function () {
            modalEditar?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            detalleEditandoId = null;
        };

        window.cerrarModalEditarDetalleLoteDesdeFondo = function (event) {
            if (event.target === modalEditar) {
                window.cerrarModalEditarDetalleLote();
            }
        };

        monedaEditar?.addEventListener('change', actualizarTipoCambioEditar);
        usarReferenciaEditar?.addEventListener('click', function () {
            @if($referenciaUsdBob)
                tipoCambioEditar.value = @js((string) $referenciaUsdBob['tipo_cambio']->valor);
                tipoCambioEditar.focus();
            @endif
        });
        document.getElementById('agregarComponenteEditar')?.addEventListener('click', () => agregarComponenteEditar());

        formEditar?.addEventListener('submit', async function (event) {
            event.preventDefault();
            limpiarErrorEditar();

            if (!detalleEditandoId) {
                return;
            }

            botonGuardarEditar.disabled = true;
            botonGuardarEditar.classList.add('opacity-60', 'cursor-not-allowed');

            try {
                const detalle = detalles[detalleEditandoId];
                const datos = detalle?.tiene_historial
                    ? new FormData()
                    : new FormData(formEditar);

                if (detalle?.tiene_historial) {
                    datos.append('cantidad_esperada', cantidadEditar.value);
                }

                datos.append('_method', 'PATCH');

                const respuesta = await fetch(`${endpointBase}/${detalleEditandoId}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: datos,
                });

                const json = await respuesta.json();
                if (!respuesta.ok || !json.ok) {
                    mostrarErrorEditar(json.message ?? 'No fue posible actualizar la línea del lote.');
                    return;
                }

                window.location.reload();
            } catch (error) {
                console.error(error);
                mostrarErrorEditar('No fue posible comunicarse con el servidor.');
            } finally {
                botonGuardarEditar.disabled = false;
                botonGuardarEditar.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });

        function limpiarErrorEliminar() {
            if (!errorEliminar) {
                return;
            }

            errorEliminar.textContent = '';
            errorEliminar.classList.add('hidden');
        }

        function mostrarErrorEliminar(mensaje) {
            if (!errorEliminar) {
                return;
            }

            errorEliminar.textContent = mensaje;
            errorEliminar.classList.remove('hidden');
        }

        window.eliminarDetalleLote = function (id) {
            const detalle = detalles[id];

            if (!detalle || !modalEliminar) {
                return;
            }

            detalleEliminarId = id;
            limpiarErrorEliminar();

            if (eliminarDetalleProducto) {
                eliminarDetalleProducto.textContent =
                    `${detalle.producto || 'Producto'}${detalle.modelo ? ' · ' + detalle.modelo : ''}`;
            }

            document
                .querySelectorAll('#tablaComposicionLote details[open]')
                .forEach((menu) => menu.removeAttribute('open'));

            modalEliminar.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');

            window.setTimeout(
                () => botonConfirmarEliminar?.focus(),
                80
            );
        };

        window.cerrarModalEliminarDetalleLote = function () {
            modalEliminar?.classList.add('hidden');
            limpiarErrorEliminar();
            detalleEliminarId = null;
            document.body.classList.remove('overflow-hidden');
        };

        window.cerrarModalEliminarDetalleLoteDesdeFondo = function (event) {
            if (event.target === modalEliminar) {
                window.cerrarModalEliminarDetalleLote();
            }
        };

        botonConfirmarEliminar?.addEventListener('click', async function () {
            if (!detalleEliminarId) {
                return;
            }

            limpiarErrorEliminar();

            this.disabled = true;
            const textoOriginal = this.textContent;
            this.textContent = 'Quitando...';

            try {
                const respuesta = await fetch(`${endpointBase}/${detalleEliminarId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                });

                const json = await respuesta.json();

                if (!respuesta.ok || !json.ok) {
                    mostrarErrorEliminar(
                        json.message ?? 'No fue posible quitar el producto del lote.'
                    );
                    return;
                }

                window.location.reload();
            } catch (error) {
                console.error(error);
                mostrarErrorEliminar(
                    'No fue posible comunicarse con el servidor.'
                );
            } finally {
                this.disabled = false;
                this.textContent = textoOriginal;
            }
        });

        const modalEspecificacion = document.getElementById('modalEspecificacionDetalleLote');
        const tituloEspecificacion = document.getElementById('tituloEspecificacionDetalle');
        const contenidoEspecificacion = document.getElementById('contenidoEspecificacionDetalle');

        function escaparHtml(valor) {
            return (valor ?? '').toString()
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function valorVisible(valor) {
            return valor === null || valor === undefined || valor === '' ? 'No informado' : escaparHtml(valor);
        }

        window.abrirModalEspecificacionDetalleLote = function (id) {
            const detalle = detalles[id];
            if (!detalle || !modalEspecificacion || !contenidoEspecificacion) {
                return;
            }

            tituloEspecificacion.textContent = `${detalle.producto || 'Producto'}${detalle.modelo ? ' · ' + detalle.modelo : ''}`;
            const e = detalle.especificacion || {};
            const componentes = detalle.componentes || [];

            contenidoEspecificacion.innerHTML = `
                <div class="grid gap-3 sm:grid-cols-2">
                    ${[
                        ['Procesador', e.procesador],
                        ['Generación', e.generacion_procesador],
                        ['RAM', e.ram_gb !== null && e.ram_gb !== undefined && e.ram_gb !== '' ? e.ram_gb + ' GB' : null],
                        ['Almacenamiento', e.almacenamiento_gb !== null && e.almacenamiento_gb !== undefined && e.almacenamiento_gb !== '' ? e.almacenamiento_gb + ' GB' : null],
                        ['Tipo de almacenamiento', e.tipo_almacenamiento],
                        ['Tarjeta gráfica', e.tarjeta_grafica],
                        ['Pantalla', e.pantalla_pulgadas ? e.pantalla_pulgadas + '"' : null],
                        ['Resolución', e.resolucion],
                        ['Sistema operativo', e.sistema_operativo],
                    ].map(([label, valor]) => `
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">${label}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">${valorVisible(valor)}</p>
                        </div>
                    `).join('')}
                </div>

                <div class="mt-5 border-t border-slate-200 pt-5">
                    <h3 class="text-sm font-bold text-slate-800">Accesorios esperados por equipo</h3>
                    ${componentes.length === 0
                        ? '<p class="mt-2 text-sm text-slate-500">No se registraron accesorios esperados.</p>'
                        : `<div class="mt-3 space-y-2">${componentes.map((componente) => `
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-sm font-bold text-slate-800">${escaparHtml(componente.nombre)}</p>
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-oneshop-dark">${Number(componente.cantidad_por_unidad || 1)} por equipo</span>
                                </div>
                                ${componente.observacion ? `<p class="mt-1 text-xs text-slate-500">${escaparHtml(componente.observacion)}</p>` : ''}
                            </div>
                        `).join('')}</div>`}
                </div>
            `;

            modalEspecificacion.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        };

        window.cerrarModalEspecificacionDetalleLote = function () {
            modalEspecificacion?.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        window.cerrarModalEspecificacionDetalleLoteDesdeFondo = function (event) {
            if (event.target === modalEspecificacion) {
                window.cerrarModalEspecificacionDetalleLote();
            }
        };

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            if (modalEliminar && !modalEliminar.classList.contains('hidden')) {
                window.cerrarModalEliminarDetalleLote();
                return;
            }

            if (modalEditar && !modalEditar.classList.contains('hidden')) {
                window.cerrarModalEditarDetalleLote();
                return;
            }

            if (modalEspecificacion && !modalEspecificacion.classList.contains('hidden')) {
                window.cerrarModalEspecificacionDetalleLote();
            }
        });
    @endif
})();
</script>
@endpush
