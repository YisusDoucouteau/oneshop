<x-layouts.oneshop
    title="Inicio | OneShop"
    page-title="Inicio"
>
<div class="space-y-8">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Resumen operativo</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950">Situación actual de OneShop</h1>
            <p class="mt-1 text-sm text-slate-500">Indicadores visibles según tu rol y sede operativa.</p>
        </div>

        @if($puedeVerReportes)
            <a
                href="{{ route('reportes.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50"
            >
                <x-ui.icon name="chart" size="17" />
                Abrir reportes
            </a>
        @endif
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-dashboard.card
            title="Equipos registrados"
            value="{{ $totalEquipos }}"
            description="Unidades visibles en tu operación"
            icon="package"
            color="blue"
        />

        <x-dashboard.card
            title="Disponibles"
            value="{{ $disponibles }}"
            description="Listos para venta"
            icon="check"
            color="green"
        />

        <x-dashboard.card
            title="Reservados"
            value="{{ $reservados }}"
            description="Comprometidos con clientes"
            icon="calendar"
            color="orange"
        />

        <x-dashboard.card
            title="Garantías abiertas"
            value="{{ $casosGarantiaAbiertos }}"
            description="Casos todavía sin cerrar"
            icon="shield"
            color="red"
        />
    </section>

    @if($puedeVerReportes && $resumenComercial && $indicadoresDecision)
        @php
            $variacion = $indicadoresDecision['variacion_ventas'];
            $variacionTexto = $variacion === null
                ? 'Sin base comparable'
                : ($variacion > 0
                    ? '↑ ' . number_format(abs($variacion), 1, ',', '.') . '% vs. mes anterior'
                    : ($variacion < 0
                        ? '↓ ' . number_format(abs($variacion), 1, ',', '.') . '% vs. mes anterior'
                        : 'Sin variación vs. mes anterior'));

            $vendedorDestacado = $indicadoresDecision['vendedor_destacado'];
        @endphp

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.14em] text-blue-700">Decisiones del mes</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950">Resumen comercial</h2>
                    <p class="mt-1 text-sm text-slate-500">Ventas registradas del mes actual con valores históricos.</p>
                </div>

                <a href="{{ route('reportes.ventas.index') }}" class="text-sm font-bold text-blue-700 hover:underline">Ver reporte de ventas</a>
            </div>

            <div class="mt-6 grid gap-5 xl:grid-cols-[1.25fr_2fr]">
                <div class="rounded-2xl border border-blue-100 bg-blue-50/60 p-5">
                    <p class="text-xs font-black uppercase tracking-[0.12em] text-blue-700">Ventas del mes</p>
                    <p class="mt-3 text-4xl font-black tracking-tight text-slate-950">Bs {{ number_format($resumenComercial['total_vendido'], 2, ',', '.') }}</p>
                    <p class="mt-2 text-sm font-bold {{ $variacion !== null && $variacion < 0 ? 'text-rose-700' : 'text-slate-600' }}">{{ $variacionTexto }}</p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Operaciones</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($resumenComercial['cantidad_ventas']) }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Ticket promedio</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">Bs {{ number_format($resumenComercial['ticket_promedio'], 2, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Margen</p>
                        <p class="mt-2 text-2xl font-black text-slate-950">{{ number_format($resumenComercial['margen_promedio'], 2, ',', '.') }}%</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-[11px] font-black uppercase tracking-wide text-slate-400">Utilidad</p>
                        <p class="mt-2 text-2xl font-black text-emerald-700">Bs {{ number_format($resumenComercial['utilidad_total'], 2, ',', '.') }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Disponibilidad</p>
                    <p class="mt-1 text-lg font-black text-slate-950">{{ number_format($indicadoresDecision['disponibilidad_porcentaje'], 1, ',', '.') }}%</p>
                    <p class="mt-1 text-xs text-slate-500">del inventario visible</p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Reservas activas</p>
                    <p class="mt-1 text-lg font-black text-slate-950">{{ number_format($indicadoresDecision['reservas_activas']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">compromisos vigentes</p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Cobros pendientes</p>
                    <p class="mt-1 text-lg font-black text-slate-950">{{ number_format($indicadoresDecision['pagos_pendientes']) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Bs {{ number_format($indicadoresDecision['monto_pendiente'], 2, ',', '.') }} pendientes de verificación</p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Vendedor destacado</p>
                    @if($vendedorDestacado)
                        <p class="mt-1 truncate text-lg font-black text-slate-950">{{ $vendedorDestacado['vendedor'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Bs {{ number_format($vendedorDestacado['total_vendido'], 2, ',', '.') }} vendidos</p>
                    @else
                        <p class="mt-1 text-lg font-black text-slate-950">—</p>
                        <p class="mt-1 text-xs text-slate-500">Sin operaciones este mes</p>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-lg font-black text-slate-950">Estado del inventario</h2>
                <p class="mt-1 text-sm text-slate-500">Distribución actual de los equipos visibles para tu operación.</p>
            </div>

            @if($puedeVerReportes)
                <a href="{{ route('reportes.inventario.index') }}" class="text-sm font-bold text-blue-700 hover:underline">Ver reporte de inventario</a>
            @endif
        </div>

        <div class="grid gap-3 md:grid-cols-2">
            @foreach($porEstado as $estado)
                @php
                    $porcentaje = $totalEquipos > 0
                        ? round(($estado->cantidad / $totalEquipos) * 100)
                        : 0;
                @endphp

                <x-dashboard.progress
                    label="{{ $estado->nombre }}"
                    value="{{ $estado->cantidad }} equipos"
                    percentage="{{ $porcentaje }}"
                />
            @endforeach
        </div>
    </section>

    @if($porAlmacen->isNotEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-black text-slate-950">Equipos por sede</h2>
                <p class="mt-1 text-sm text-slate-500">Distribución actual del inventario formal.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach($porAlmacen as $almacen)
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="font-bold text-slate-900">{{ $almacen->nombre }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ $almacen->ciudad }}</p>
                        <p class="mt-3 text-2xl font-black text-blue-700">{{ $almacen->equipos_count }}</p>
                        <p class="text-xs font-semibold text-slate-500">equipos activos</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-2 xl:items-stretch">
        <x-dashboard.equipment-list :equipos="$ultimosEquipos" />
        <x-dashboard.activity :movimientos="$ultimosMovimientos" />
    </section>
</div>
</x-layouts.oneshop>
