<x-layouts.oneshop
    title="Reporte de ventas | OneShop"
    page-title="Ventas"
>
@php
    $etiquetaEstado = fn ($estado) => match ((string) $estado) {
        'REGISTRADA' => 'Registrada',
        'ANULADA' => 'Anulada',
        default => ucwords(mb_strtolower(str_replace('_', ' ', (string) $estado), 'UTF-8')),
    };

    $queryExportacion = request()->except('page');
    $desde = \Carbon\Carbon::parse($filtros['fecha_desde']);
    $hasta = \Carbon\Carbon::parse($filtros['fecha_hasta']);
    $periodo = $desde->format('d/m/Y') . ' — ' . $hasta->format('d/m/Y');

    $basePreset = request()->except(['page', 'fecha_desde', 'fecha_hasta']);
    $hoy = now();
    $presets = [
        'Hoy' => [$hoy->copy()->toDateString(), $hoy->copy()->toDateString()],
        'Esta semana' => [$hoy->copy()->startOfWeek()->toDateString(), $hoy->copy()->endOfWeek()->toDateString()],
        'Este mes' => [$hoy->copy()->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()],
        'Mes anterior' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()],
    ];

    $deltaTotal = $comparativo['variaciones']['total_vendido'] ?? null;
    $deltaUtilidad = $comparativo['variaciones']['utilidad_total'] ?? null;
    $deltaOperaciones = $comparativo['variaciones']['cantidad_ventas'] ?? null;

    $deltaTexto = function (?float $valor): string {
        if ($valor === null) {
            return 'Sin base anterior';
        }

        if ($valor > 0) {
            return '↑ ' . number_format(abs($valor), 1, ',', '.') . '%';
        }

        if ($valor < 0) {
            return '↓ ' . number_format(abs($valor), 1, ',', '.') . '%';
        }

        return 'Sin variación';
    };

    $serie = $evolucion->values();
    $chartWidth = 760;
    $chartHeight = 180;
    $paddingX = 18;
    $paddingY = 18;
    $maxSerie = max(1, (float) $serie->max('total'));
    $cantidadPuntos = $serie->count();

    $puntos = $serie->map(function ($punto, $indice) use ($chartWidth, $chartHeight, $paddingX, $paddingY, $maxSerie, $cantidadPuntos) {
        $x = $cantidadPuntos <= 1
            ? $chartWidth / 2
            : $paddingX + (($chartWidth - ($paddingX * 2)) * ($indice / ($cantidadPuntos - 1)));

        $y = $chartHeight - $paddingY - (((float) $punto['total'] / $maxSerie) * ($chartHeight - ($paddingY * 2)));

        return round($x, 2) . ',' . round($y, 2);
    })->implode(' ');
@endphp

<div class="space-y-7">
    <nav class="flex items-center gap-2 text-sm font-semibold text-slate-500" aria-label="Ruta de navegación">
        <a href="{{ route('reportes.index') }}" class="hover:text-blue-700">Reportes</a>
        <span class="text-slate-300">/</span>
        <span class="text-slate-900">Ventas</span>
    </nav>

    <section class="flex flex-wrap items-start justify-between gap-5">
        <div class="max-w-3xl">
            <h1 class="text-3xl font-black tracking-tight text-slate-950">Reporte de ventas</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Consulta operaciones comerciales por periodo, cliente, vendedor y estado.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reportes.ventas.csv', $queryExportacion) }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Exportar CSV</a>
            <a href="{{ route('reportes.ventas.imprimir', $queryExportacion) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reportes.ventas.pdf', $queryExportacion) }}" target="_blank" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-blue-800">Generar PDF</a>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="mr-1 text-xs font-black uppercase tracking-[0.12em] text-slate-400">Periodo</span>
                @foreach($presets as $etiqueta => [$presetDesde, $presetHasta])
                    @php
                        $activo = $filtros['fecha_desde'] === $presetDesde && $filtros['fecha_hasta'] === $presetHasta;
                    @endphp
                    <a
                        href="{{ route('reportes.ventas.index', array_merge($basePreset, ['fecha_desde' => $presetDesde, 'fecha_hasta' => $presetHasta])) }}"
                        class="rounded-lg px-3 py-1.5 text-xs font-bold {{ $activo ? 'bg-blue-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}"
                    >
                        {{ $etiqueta }}
                    </a>
                @endforeach
            </div>
            <p class="text-sm font-bold text-slate-700">{{ $periodo }}</p>
        </div>

        <form method="GET" action="{{ route('reportes.ventas.index') }}" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-2 xl:grid-cols-6">
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Desde</label>
                <input type="date" name="fecha_desde" value="{{ $filtros['fecha_desde'] }}" class="input-oneshop w-full">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Hasta</label>
                <input type="date" name="fecha_hasta" value="{{ $filtros['fecha_hasta'] }}" class="input-oneshop w-full">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Vendedor</label>
                <select name="vendedor_id" class="input-oneshop w-full">
                    <option value="">Todos</option>
                    @foreach($vendedores as $vendedor)
                        <option value="{{ $vendedor->id }}" @selected((string) ($filtros['vendedor_id'] ?? '') === (string) $vendedor->id)>{{ $vendedor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Cliente</label>
                <select name="cliente_id" class="input-oneshop w-full">
                    <option value="">Todos</option>
                    @foreach($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string) ($filtros['cliente_id'] ?? '') === (string) $cliente->id)>{{ $cliente->nombre_completo }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-600">Estado</label>
                <select name="estado_venta" class="input-oneshop w-full">
                    <option value="REGISTRADA" @selected(($filtros['estado_venta'] ?? 'REGISTRADA') === 'REGISTRADA')>Registradas</option>
                    <option value="ANULADA" @selected(($filtros['estado_venta'] ?? '') === 'ANULADA')>Anuladas</option>
                    <option value="TODAS" @selected(($filtros['estado_venta'] ?? '') === 'TODAS')>Todas</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">Aplicar</button>
                <a href="{{ route('reportes.ventas.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Limpiar</a>
            </div>
        </form>
    </section>

    <section class="grid gap-4 xl:grid-cols-[1.4fr_2fr]">
        <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-6 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Ventas del periodo</p>
            <p class="mt-3 text-4xl font-black tracking-tight text-slate-950">Bs {{ number_format($resumen['total_vendido'], 2, ',', '.') }}</p>
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-black text-slate-700 shadow-sm">{{ $deltaTexto($deltaTotal) }}</span>
                <span class="text-xs text-slate-500">frente al periodo anterior equivalente</span>
            </div>
            <p class="mt-5 border-t border-blue-100 pt-4 text-xs leading-5 text-slate-500">
                Periodo anterior: {{ \Carbon\Carbon::parse($comparativo['periodo_anterior']['desde'])->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($comparativo['periodo_anterior']['hasta'])->format('d/m/Y') }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-reportes.metric label="Operaciones" :value="number_format($resumen['cantidad_ventas'])" :caption="$deltaTexto($deltaOperaciones) . ' vs. periodo anterior'" />
            <x-reportes.metric label="Ticket promedio" value="Bs {{ number_format($resumen['ticket_promedio'], 2, ',', '.') }}" caption="Promedio por venta" />
            <x-reportes.metric label="Utilidad" value="Bs {{ number_format($resumen['utilidad_total'], 2, ',', '.') }}" :caption="$deltaTexto($deltaUtilidad) . ' vs. periodo anterior'" tone="emerald" />
            <x-reportes.metric label="Margen" value="{{ number_format($resumen['margen_promedio'], 2, ',', '.') }}%" caption="Margen histórico del periodo" tone="blue" />
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-[2fr_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Tendencia</p>
                    <h2 class="mt-1 text-lg font-black text-slate-950">Evolución de ventas</h2>
                </div>
                <p class="text-xs font-semibold text-slate-400">{{ number_format($resumen['cantidad_ventas']) }} operaciones</p>
            </div>

            @if($serie->isEmpty())
                <div class="mt-5 rounded-xl bg-slate-50 px-4 py-12 text-center text-sm text-slate-500">Sin ventas para graficar en este periodo.</div>
            @else
                <div class="mt-5 overflow-hidden rounded-xl bg-slate-50 p-3">
                    <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="h-44 w-full" preserveAspectRatio="none" role="img" aria-label="Evolución del total vendido">
                        <line x1="18" y1="45" x2="742" y2="45" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="18" y1="90" x2="742" y2="90" stroke="#e2e8f0" stroke-width="1" />
                        <line x1="18" y1="135" x2="742" y2="135" stroke="#e2e8f0" stroke-width="1" />
                        @if($cantidadPuntos === 1)
                            @php
                                [$cx, $cy] = explode(',', $puntos);
                            @endphp
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="6" fill="#2563eb" />
                        @else
                            <polyline points="{{ $puntos }}" fill="none" stroke="#2563eb" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
                        @endif
                    </svg>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Desempeño</p>
            <h2 class="mt-1 text-lg font-black text-slate-950">Vendedores</h2>
            <div class="mt-4 space-y-3">
                @forelse($ventasPorVendedor as $item)
                    <a
                        href="{{ route('reportes.ventas.index', array_merge(request()->except(['page', 'vendedor_id']), ['vendedor_id' => $item['vendedor_id']])) }}"
                        class="block rounded-xl border border-slate-100 p-3 transition hover:border-blue-200 hover:bg-blue-50/40"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="truncate text-sm font-bold text-slate-900">{{ $item['vendedor'] }}</p>
                            <p class="whitespace-nowrap text-sm font-black text-slate-950">Bs {{ number_format($item['total_vendido'], 2, ',', '.') }}</p>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">{{ number_format($item['cantidad_ventas']) }} operaciones · utilidad Bs {{ number_format($item['utilidad_generada'], 2, ',', '.') }}</p>
                    </a>
                @empty
                    <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Sin ventas por vendedor para el periodo.</p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Detalle</p>
            <div class="mt-1 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-black text-slate-950">Operaciones</h2>
                    <p class="mt-1 text-sm text-slate-500">Busca, ordena y pagina la consulta directamente en la tabla.</p>
                </div>
                <p class="text-xs font-bold text-slate-400">{{ number_format($ventas->count()) }} registros filtrados</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table
                id="tabla-reporte-ventas"
                data-oneshop-datatable
                data-order-column="1"
                data-order-direction="desc"
                data-page-length="25"
                class="min-w-full text-sm"
            >
                <thead class="bg-slate-50 text-left text-[11px] font-black uppercase tracking-[0.09em] text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Venta</th>
                        <th class="px-5 py-3">Fecha</th>
                        <th class="px-5 py-3">Cliente</th>
                        <th class="px-5 py-3">Vendedor</th>
                        <th class="px-5 py-3 text-right">Total</th>
                        <th class="px-5 py-3 text-right">Costo</th>
                        <th class="px-5 py-3 text-right">Utilidad</th>
                        <th class="px-5 py-3 text-right">Margen</th>
                        <th class="px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($ventas as $venta)
                        @php
                            $costo = (float) $venta->detalles->sum(fn ($detalle) => (float) $detalle->costo_unitario_snapshot * (int) $detalle->cantidad);
                            $total = (float) $venta->total;
                            $utilidad = $total - $costo;
                            $margen = $total > 0 ? ($utilidad / $total) * 100 : 0;
                            $cliente = $venta->cliente_nombre_snapshot ?: $venta->cliente?->nombre_completo ?: 'Cliente no identificado';
                        @endphp
                        <tr class="hover:bg-slate-50/70">
                            <td class="whitespace-nowrap px-5 py-4 font-bold text-slate-950">{{ $venta->numero }}</td>
                            <td data-order="{{ $venta->fecha_venta?->timestamp ?? 0 }}" class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $venta->fecha_venta?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $cliente }}</td>
                            <td class="px-5 py-4 text-slate-700">{{ $venta->vendedor?->name ?? 'No disponible' }}</td>
                            <td data-order="{{ $total }}" class="whitespace-nowrap px-5 py-4 text-right font-bold text-slate-950">Bs {{ number_format($total, 2, ',', '.') }}</td>
                            <td data-order="{{ $costo }}" class="whitespace-nowrap px-5 py-4 text-right text-slate-600">Bs {{ number_format($costo, 2, ',', '.') }}</td>
                            <td data-order="{{ $utilidad }}" class="whitespace-nowrap px-5 py-4 text-right font-bold {{ $utilidad >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">Bs {{ number_format($utilidad, 2, ',', '.') }}</td>
                            <td data-order="{{ $margen }}" class="whitespace-nowrap px-5 py-4 text-right font-bold text-slate-700">{{ number_format($margen, 2, ',', '.') }}%</td>
                            <td class="whitespace-nowrap px-5 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">{{ $etiquetaEstado($venta->estado) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <p class="text-xs leading-5 text-slate-500">
        Los costos y utilidades usan los valores históricos congelados al registrar cada venta; cambios posteriores de precio o tipo de cambio no modifican el historial.
    </p>
</div>
</x-layouts.oneshop>
