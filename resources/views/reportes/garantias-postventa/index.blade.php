<x-layouts.oneshop title="Garantías y postventa | OneShop" page-title="Garantías y postventa">
@php $queryExportacion = request()->except('page'); @endphp
<div class="space-y-7">
    <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500" aria-label="Ruta de navegación">
        <a href="{{ route('reportes.index') }}" class="hover:text-blue-700">Reportes</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900">Garantías y postventa</span>
    </nav>

    <section class="flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-black tracking-tight text-slate-950">Garantías y postventa</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Seguimiento de casos posteriores a la venta, tiempos de atención, resoluciones y reemplazos de equipos.
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reportes.garantias-postventa.csv', $queryExportacion) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Exportar CSV</a>
            <a href="{{ route('reportes.garantias-postventa.imprimir', $queryExportacion) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reportes.garantias-postventa.pdf', $queryExportacion) }}" target="_blank" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Generar PDF</a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-reportes.metric label="Casos activos" :value="number_format($resumen['activos'])" caption="Requieren seguimiento o resolución" tone="amber" />
        <x-reportes.metric label="Casos cerrados" :value="number_format($resumen['cerrados'])" caption="Resueltos dentro del filtro aplicado" tone="green" />
        <x-reportes.metric label="Con reemplazo" :value="number_format($resumen['con_reemplazo'])" caption="Casos que terminaron en cambio de equipo" tone="violet" />
        <x-reportes.metric label="Tiempo promedio" :value="number_format($resumen['tiempo_promedio_dias'], 1, ',', '.') . ' días'" caption="Promedio de casos ya cerrados" tone="blue" />
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('reportes.garantias-postventa.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" class="input-oneshop w-full">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" class="input-oneshop w-full">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Estado</label>
                <select name="estado" class="input-oneshop w-full">
                    <option value="">Todos</option>
                    @foreach($estadosCaso as $estado)
                        <option value="{{ $estado }}" @selected(($filtros['estado'] ?? '') === $estado)>{{ str($estado)->replace('_', ' ')->lower()->ucfirst() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Cliente</label>
                <select name="cliente_id" class="input-oneshop w-full">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string)($filtros['cliente_id'] ?? '') === (string)$cliente->id)>{{ $cliente->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Aplicar</button>
                <a href="{{ route('reportes.garantias-postventa.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Seguimiento postventa</p>
            <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Casos de garantía</h2>
                    <p class="mt-1 text-sm text-slate-500">La búsqueda rápida de la tabla permite localizar caso, cliente, equipo, motivo, diagnóstico o resolución.</p>
                </div>
                <p class="text-xs font-bold text-slate-400">
                    {{ number_format($casos->count()) }}
                    {{ $casos->count() === 1 ? 'caso filtrado' : 'casos filtrados' }}
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table id="tabla-reporte-garantias" data-oneshop-datatable data-order-column="4" data-order-direction="desc" data-page-length="25" class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.09em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Caso</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3">Equipo</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Apertura</th>
                        <th class="px-4 py-3">Atención</th>
                        <th class="px-4 py-3">Diagnóstico / resolución</th>
                        <th class="px-4 py-3">Reemplazo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($casos as $caso)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="font-bold text-slate-950">{{ $caso['numero'] }}</div>
                                <div class="mt-1 max-w-xs truncate text-xs text-slate-500" title="{{ $caso['motivo'] }}">{{ $caso['motivo'] }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $caso['cliente'] }}</td>
                            <td class="px-4 py-4">
                                <div class="font-semibold text-slate-800">{{ $caso['equipo'] }}</div>
                                <div class="mt-1 text-xs text-slate-500">{{ $caso['producto'] }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $caso['estado_humano'] }}</span></td>
                            <td data-order="{{ $caso['fecha_apertura']?->timestamp ?? 0 }}" class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $caso['fecha_apertura']?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td data-order="{{ $caso['horas_atencion'] }}" class="whitespace-nowrap px-4 py-4 font-semibold text-slate-700">{{ number_format($caso['dias_atencion'], 1, ',', '.') }} días</td>
                            <td class="max-w-sm px-4 py-4">
                                <div class="text-slate-700">{{ $caso['diagnostico'] ?: 'Sin diagnóstico registrado' }}</div>
                                @if($caso['resolucion'])<div class="mt-1 text-xs font-semibold text-slate-500">Resolución: {{ $caso['resolucion'] }}</div>@endif
                            </td>
                            <td class="px-4 py-4">
                                @if($caso['tiene_reemplazo'])
                                    <div class="font-semibold text-slate-800">{{ $caso['equipo_reemplazo'] ?: 'Reemplazo registrado' }}</div>
                                    @if($caso['diferencia_ajuste'] !== null)
                                        <div class="mt-1 text-xs text-slate-500">{{ $caso['moneda_ajuste'] ?: 'BOB' }} {{ number_format(abs($caso['diferencia_ajuste']), 2, ',', '.') }} · {{ str($caso['estado_ajuste'] ?? '')->replace('_', ' ')->title() }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">Sin reemplazo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-layouts.oneshop>
