<x-layouts.oneshop
    title="Trazabilidad de equipos | OneShop"
    page-title="Trazabilidad de equipos"
>
<div class="space-y-7">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Expediente individual</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Trazabilidad de equipos</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Localiza un equipo y consulta su historia completa desde la adquisición y preparación hasta inventario, comercialización y postventa.
            </p>
        </div>
        <a href="{{ route('reportes.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">
            Volver a reportes
        </a>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('reportes.trazabilidad.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[260px] flex-1">
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Buscar equipo</label>
                <input
                    type="search"
                    name="q"
                    value="{{ $filtros['q'] ?? '' }}"
                    placeholder="Código interno, serial, producto o modelo"
                    class="input-oneshop w-full"
                >
            </div>
            <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Buscar</button>
            <a href="{{ route('reportes.trazabilidad.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpiar</a>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Equipos localizados</p>
            <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Seleccionar expediente</h2>
                    <p class="mt-1 text-sm text-slate-500">La tabla permite búsqueda rápida adicional, ordenamiento y paginación.</p>
                </div>
                <p class="text-xs font-bold text-slate-400">{{ number_format($equipos->count()) }} equipos</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table data-oneshop-datatable data-order-column="0" data-order-direction="asc" data-page-length="25" class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.09em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Serial</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3">Procedencia</th>
                        <th class="px-4 py-3">Ubicación</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($equipos as $equipo)
                        @php
                            $unidad = $equipo->incorporacionUnidad?->unidadAdquirida;
                            $lote = $unidad?->detalleLote?->lote ?: $equipo->detalleLote?->lote;
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-950">{{ $equipo->codigo_interno ?: 'EQ-' . $equipo->id }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $equipo->serial_fabricante ?: '—' }}</td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">{{ $equipo->producto?->nombre ?: 'Producto no registrado' }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ collect([$equipo->producto?->marca?->nombre, $equipo->producto?->modelo])->filter()->implode(' · ') ?: '—' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $lote?->codigo ?: 'Sin lote' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $equipo->almacenActual?->nombre ?: 'No registrada' }}</td>
                            <td class="px-4 py-4">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $equipo->estadoActual?->nombre ?: 'Sin estado' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <a href="{{ route('reportes.trazabilidad.detalle', $equipo) }}" class="inline-flex rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white hover:bg-slate-800">
                                    Ver expediente
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No se encontraron equipos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-layouts.oneshop>
