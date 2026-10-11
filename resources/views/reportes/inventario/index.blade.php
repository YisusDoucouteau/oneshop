<x-layouts.oneshop
    title="Reporte de inventario | OneShop"
    page-title="Inventario"
>
@php
    $queryExportacion = request()->except('page');
    $disponibilidad = $resumen['total'] > 0
        ? ($resumen['disponibles'] / $resumen['total']) * 100
        : 0;
@endphp

<div class="space-y-7">
    <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500" aria-label="Ruta de navegación">
        <a href="{{ route('reportes.index') }}" class="hover:text-blue-700">Reportes</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900">Inventario</span>
    </nav>

    <section class="flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-black tracking-tight text-slate-950">Reporte de inventario</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Consulta equipos formalizados por código, serial, producto, almacén y estado.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reportes.inventario.csv', $queryExportacion) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Exportar CSV</a>
            <a href="{{ route('reportes.inventario.imprimir', $queryExportacion) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reportes.inventario.pdf', $queryExportacion) }}" target="_blank" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Generar PDF</a>
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.25fr_2fr]">
        <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-6 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Disponibilidad actual</p>
            <div class="mt-3 flex items-end gap-3">
                <p class="text-4xl font-black tracking-tight text-slate-950">{{ number_format($resumen['disponibles']) }}</p>
                <p class="pb-1 text-sm font-semibold text-slate-500">de {{ number_format($resumen['total']) }} equipos</p>
            </div>
            <div class="mt-5 h-2 overflow-hidden rounded-full bg-blue-100">
                <div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, max(0, $disponibilidad)) }}%"></div>
            </div>
            <p class="mt-3 text-xs text-slate-500">{{ number_format($disponibilidad, 1, ',', '.') }}% del inventario formal está disponible.</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-reportes.metric label="Equipos" :value="number_format($resumen['total'])" caption="Registros activos" />
            <x-reportes.metric label="Reservados" :value="number_format($resumen['reservados'])" caption="Comprometidos comercialmente" tone="violet" />
            <x-reportes.metric label="Vendidos" :value="number_format($resumen['vendidos'])" caption="Histórico formal" />
            <x-reportes.metric label="Con precio vigente" :value="number_format($resumen['con_precio'])" caption="Con referencia comercial" tone="blue" />
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('reportes.inventario.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_1fr_auto]">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Almacén</label>
                <select name="almacen_id" class="input-oneshop w-full">
                    <option value="">Todos</option>
                    @foreach($almacenes as $almacen)
                        <option value="{{ $almacen->id }}" @selected((string) ($filtros['almacen_id'] ?? '') === (string) $almacen->id)>
                            {{ $almacen->nombre }}@if($almacen->ciudad) · {{ $almacen->ciudad }}@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Estado</label>
                <select name="estado_id" class="input-oneshop w-full">
                    <option value="">Todos</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado->id }}" @selected((string) ($filtros['estado_id'] ?? '') === (string) $estado->id)>{{ $estado->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Aplicar</button>
                <a href="{{ route('reportes.inventario.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Detalle</p>
            <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Equipos</h2>
                    <p class="mt-1 text-sm text-slate-500">Busca, ordena y pagina la consulta directamente en la tabla.</p>
                </div>
                <p class="text-xs font-bold text-slate-400">{{ number_format($equipos->count()) }} registros filtrados</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table
                id="tabla-reporte-inventario"
                data-oneshop-datatable
                data-order-column="6"
                data-order-direction="desc"
                data-page-length="25"
                class="min-w-full text-sm"
            >
                <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.09em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-5 py-3">Serial</th>
                        <th class="px-5 py-3">Producto</th>
                        <th class="px-5 py-3">Almacén</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Precio vigente</th>
                        <th class="px-5 py-3">Registro</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($equipos as $equipo)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-950">{{ $equipo->codigo_interno }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $equipo->serial_fabricante ?: '—' }}</td>
                            <td class="px-5 py-4">
                                <p class="font-semibold text-slate-900">{{ $equipo->producto?->nombre ?? 'Sin producto' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $equipo->producto?->marca?->nombre ?? 'Sin marca' }}@if($equipo->producto?->modelo) · {{ $equipo->producto->modelo }}@endif</p>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-700">{{ $equipo->almacenActual?->nombre ?? 'Sin almacén' }}</td>
                            <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $equipo->estadoActual?->nombre ?? 'Sin estado' }}</span></td>
                            <td data-order="{{ $equipo->precioVigente ? (float) $equipo->precioVigente->precio_publico : -1 }}" class="whitespace-nowrap px-5 py-4 text-right font-bold text-slate-950">
                                @if($equipo->precioVigente)
                                    Bs {{ number_format((float) $equipo->precioVigente->precio_publico, 2, ',', '.') }}
                                @else
                                    <span class="font-normal text-slate-400">Sin precio</span>
                                @endif
                            </td>
                            <td data-order="{{ $equipo->fecha_registro?->timestamp ?? 0 }}" class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $equipo->fecha_registro?->format('d/m/Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-layouts.oneshop>
