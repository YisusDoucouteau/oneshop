<x-layouts.oneshop title="Abastecimiento y logística | OneShop" page-title="Abastecimiento y logística">
@php $queryExportacion = request()->except('page'); @endphp
<div class="space-y-7">
    <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500" aria-label="Ruta de navegación">
        <a href="{{ route('reportes.index') }}" class="hover:text-blue-700">Reportes</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900">Abastecimiento y logística</span>
    </nav>

    <section class="flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-black tracking-tight text-slate-950">Abastecimiento y logística</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Seguimiento gerencial del ciclo de lotes desde la compra y recepción en Cochabamba hasta la incorporación en Oruro.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reportes.abastecimiento-logistica.csv', $queryExportacion) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Exportar CSV</a>
            <a href="{{ route('reportes.abastecimiento-logistica.imprimir', $queryExportacion) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reportes.abastecimiento-logistica.pdf', $queryExportacion) }}" target="_blank" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Generar PDF</a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-reportes.metric label="Pendientes de envío" :value="number_format($resumen['pendientes_envio'])" caption="Unidades listas en Cochabamba" tone="amber" />
        <x-reportes.metric label="En tránsito" :value="number_format($resumen['en_transito'])" caption="Unidades despachadas hacia Oruro" tone="blue" />
        <x-reportes.metric label="Pendientes de incorporación" :value="number_format($resumen['pendientes_incorporacion'])" caption="Recibidas en Oruro aún no formalizadas" tone="violet" />
        <x-reportes.metric label="Incidencias activas" :value="number_format($resumen['incidencias_activas'])" caption="Casos logísticos pendientes de resolver" tone="amber" />
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('reportes.abastecimiento-logistica.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div><label class="mb-1.5 block text-xs font-bold text-slate-600">Desde</label><input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] ?? '' }}" class="input-oneshop w-full"></div>
            <div><label class="mb-1.5 block text-xs font-bold text-slate-600">Hasta</label><input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] ?? '' }}" class="input-oneshop w-full"></div>
            <div><label class="mb-1.5 block text-xs font-bold text-slate-600">Proveedor</label><select name="proveedor_id" class="input-oneshop w-full"><option value="">Todos</option>@foreach($proveedores as $proveedor)<option value="{{ $proveedor->id }}" @selected((string)($filtros['proveedor_id'] ?? '') === (string)$proveedor->id)>{{ $proveedor->nombre }}</option>@endforeach</select></div>
            <div><label class="mb-1.5 block text-xs font-bold text-slate-600">Estado del lote</label><select name="estado_lote" class="input-oneshop w-full"><option value="">Todos</option>@foreach($estadosLote as $estado)<option value="{{ $estado }}" @selected(($filtros['estado_lote'] ?? '') === $estado)>{{ str($estado)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
            <div class="flex items-end gap-2"><button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Aplicar</button><a href="{{ route('reportes.abastecimiento-logistica.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpiar</a></div>
        </form>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Control del ciclo</p>
            <div class="mt-1 flex flex-wrap items-end justify-between gap-3"><div><h2 class="text-lg font-black text-slate-950">Lotes</h2><p class="mt-1 text-sm text-slate-500">Los hitos son acumulativos: una unidad incorporada también cuenta como preparada, enviada y recibida.</p></div><p class="text-xs font-bold text-slate-400">{{ number_format($lotes->count()) }} lotes filtrados</p></div>
        </div>
        <div class="overflow-x-auto">
            <table id="tabla-reporte-logistica" data-oneshop-datatable data-order-column="2" data-order-direction="desc" data-page-length="25" class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.09em] text-slate-500"><tr><th class="px-4 py-3">Lote</th><th class="px-4 py-3">Proveedor</th><th class="px-4 py-3">Compra</th><th class="px-4 py-3 text-center">Esperadas</th><th class="px-4 py-3 text-center">CBBA</th><th class="px-4 py-3 text-center">Preparadas</th><th class="px-4 py-3 text-center">Enviadas</th><th class="px-4 py-3 text-center">Oruro</th><th class="px-4 py-3 text-center">Inventario</th><th class="px-4 py-3 text-center">Incid.</th><th class="px-4 py-3 text-right">Adquisición</th><th class="px-4 py-3">Estado del ciclo</th></tr></thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($lotes as $lote)
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-4 py-4 font-bold text-slate-950">{{ $lote['codigo'] }}</td><td class="px-4 py-4 text-slate-700">{{ $lote['proveedor'] }}</td><td data-order="{{ $lote['fecha_compra']?->timestamp ?? 0 }}" class="whitespace-nowrap px-4 py-4 text-slate-600">{{ $lote['fecha_compra']?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-4 text-center font-semibold">{{ $lote['esperadas'] }}</td><td class="px-4 py-4 text-center">{{ $lote['recibidas_cbba'] }}</td><td class="px-4 py-4 text-center">{{ $lote['preparadas'] }}</td><td class="px-4 py-4 text-center">{{ $lote['enviadas'] }}</td><td class="px-4 py-4 text-center">{{ $lote['recibidas_oruro'] }}</td><td class="px-4 py-4 text-center">{{ $lote['incorporadas'] }}</td>
                            <td class="px-4 py-4 text-center">@if($lote['incidencias_activas'] > 0)<span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-black text-amber-800">{{ $lote['incidencias_activas'] }}</span>@else<span class="text-slate-400">0</span>@endif</td>
                            <td data-order="{{ $lote['costo_adquisicion_bob'] }}" class="whitespace-nowrap px-4 py-4 text-right font-bold text-slate-950">Bs {{ number_format($lote['costo_adquisicion_bob'], 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $lote['estado_ciclo'] }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
</x-layouts.oneshop>
