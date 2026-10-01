@php
    /*
    |--------------------------------------------------------------------------
    | Solo se consideran costos activos
    |--------------------------------------------------------------------------
    |
    | Los costos anulados permanecen para auditoría,
    | pero no afectan los valores financieros mostrados aquí.
    |
    */

    $costosActivos = $lote
        ->costos
        ->where('estado', 'ACTIVO');

    $totalOrigen = $costosActivos
        ->sum('monto_origen');

    $totalBob = $costosActivos
        ->sum('monto_bob');

    $cantidadCostos = $costosActivos
        ->count();
@endphp

<section
    id="resumen-financiero"
    class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm"
>
    {{-- HEADER --}}
    <div class="flex flex-col gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-xs font-black text-oneshop-primary shadow-sm">
                Bs
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                    Consolidado
                </p>

                <h2 class="mt-0.5 text-lg font-bold text-slate-950">
                    Resumen financiero
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Consolidado de costos activos asociados a la importación.
                </p>
            </div>
        </div>

        <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">
            {{ $cantidadCostos }}
            {{ $cantidadCostos === 1 ? 'costo activo' : 'costos activos' }}
        </span>
    </div>

    {{-- RESUMEN --}}
    <div class="p-6">
        <div class="grid gap-4 md:grid-cols-3">
            {{-- CANTIDAD --}}
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.1em] text-slate-500">
                            Registros
                        </p>

                        <p class="mt-2 text-sm font-semibold text-slate-700">
                            Costos activos
                        </p>
                    </div>

                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500">
                        <x-ui.icon name="list" size="16" />
                    </div>
                </div>

                <p class="mt-5 text-3xl font-black tracking-tight text-slate-950">
                    {{ $cantidadCostos }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    No incluye costos anulados.
                </p>
            </article>

            {{-- ORIGEN --}}
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.1em] text-slate-500">
                            Moneda de origen
                        </p>

                        <p class="mt-2 text-sm font-semibold text-slate-700">
                            Total origen
                        </p>
                    </div>

                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-slate-500">
                        <x-ui.icon name="receipt" size="16" />
                    </div>
                </div>

                <p class="mt-5 text-2xl font-black tracking-tight text-slate-950">
                    {{ number_format((float) $totalOrigen, 2) }}
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Suma de los importes registrados en origen.
                </p>
            </article>

            {{-- TOTAL BOB --}}
            <article class="rounded-2xl border border-blue-200 bg-blue-50/70 p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.1em] text-oneshop-primary">
                            Consolidado en bolivianos
                        </p>

                        <p class="mt-2 text-sm font-bold text-oneshop-dark">
                            Total costos Bs
                        </p>
                    </div>

                    <div class="flex h-9 w-9 items-center justify-center rounded-lg border border-blue-200 bg-white text-xs font-black text-oneshop-primary">
                        Bs
                    </div>
                </div>

                <p class="mt-5 text-3xl font-black tracking-tight text-oneshop-dark">
                    Bs {{ number_format((float) $totalBob, 2) }}
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-600">
                    Considera únicamente costos activos convertidos a BOB.
                </p>
            </article>
        </div>

        <div class="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
            <div class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white text-slate-500">
                <x-ui.icon name="info" size="14" />
            </div>

            <p class="text-xs leading-5 text-slate-600">
                Los costos anulados permanecen disponibles para auditoría, pero no afectan este resumen financiero.
            </p>
        </div>
    </div>
</section>
