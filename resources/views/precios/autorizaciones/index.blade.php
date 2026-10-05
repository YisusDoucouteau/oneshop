<x-layouts.oneshop
    title="Autorizaciones de precio | OneShop"
    page-title="Autorizaciones de precio"
>
<div class="space-y-6">
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">
                Control comercial
            </p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950">
                Solicitudes de descuento
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Revisa propuestas que quedaron por debajo del mínimo autorizado. La decisión conserva precio, costo, utilidad, usuario, fecha y medio de respuesta.
            </p>
        </div>

        <div class="rounded-xl bg-slate-50 px-5 py-4 text-center">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Pendientes
            </p>
            <p class="mt-1 text-3xl font-black text-slate-950">
                {{ $pendientes->count() }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <section class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-950">
                    Pendientes de decisión
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Las más antiguas aparecen primero.
                </p>
            </div>
        </div>

        @forelse($pendientes as $solicitud)
            @php
                $precioEquipo = $solicitud->precioEquipo;
                $equipo = $precioEquipo?->equipo;
                $producto = $equipo?->producto;
            @endphp

            <article class="rounded-2xl border border-amber-200 bg-white shadow-sm">
                <div class="grid gap-5 p-6 xl:grid-cols-[1.25fr_.9fr_1.2fr]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-700">
                                Pendiente
                            </span>
                            <span class="text-xs text-slate-400">
                                {{ $solicitud->created_at?->format('d/m/Y H:i') }}
                            </span>
                        </div>

                        <h3 class="mt-3 text-lg font-bold text-slate-950">
                            {{ $producto?->marca?->nombre }} {{ $producto?->nombre }} {{ $producto?->modelo }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ $equipo?->codigo_interno ?? 'Equipo' }} · Solicitado por {{ $solicitud->solicitadoPor?->name ?? 'Usuario' }}
                        </p>

                        @if($solicitud->motivo)
                            <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                                <span class="font-semibold text-slate-800">Motivo:</span>
                                {{ $solicitud->motivo }}
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Publicado</p>
                            <p class="mt-1 text-lg font-bold text-slate-950">Bs {{ number_format($solicitud->precio_publico_snapshot, 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-blue-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Solicitado</p>
                            <p class="mt-1 text-lg font-bold text-blue-950">Bs {{ number_format($solicitud->precio_solicitado, 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Costo congelado</p>
                            <p class="mt-1 font-bold text-slate-950">Bs {{ number_format($solicitud->costo_total_snapshot, 2) }}</p>
                        </div>
                        <div class="rounded-xl bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Utilidad proyectada</p>
                            <p class="mt-1 font-bold text-emerald-700">Bs {{ number_format($solicitud->utilidad_proyectada, 2) }}</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        <form
                            method="POST"
                            action="{{ route('precios.autorizaciones.aprobar', $solicitud) }}"
                            class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4"
                        >
                            @csrf

                            <p class="text-sm font-bold text-emerald-900">
                                Aprobar propuesta
                            </p>

                            <select
                                name="medio_respuesta"
                                class="input-oneshop mt-3 w-full"
                                required
                            >
                                <option value="SISTEMA">Sistema</option>
                                <option value="LLAMADA">Llamada</option>
                                <option value="WHATSAPP">WhatsApp</option>
                                <option value="PRESENCIAL">Presencial</option>
                            </select>

                            <textarea
                                name="motivo_respuesta"
                                rows="2"
                                class="input-oneshop mt-3 w-full"
                                placeholder="Observación opcional"
                            ></textarea>

                            <button
                                type="submit"
                                class="btn-primary mt-3 w-full"
                            >
                                Aprobar
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('precios.autorizaciones.rechazar', $solicitud) }}"
                            class="rounded-xl border border-red-200 bg-red-50/50 p-4"
                        >
                            @csrf

                            <p class="text-sm font-bold text-red-900">
                                Rechazar propuesta
                            </p>

                            <input
                                type="hidden"
                                name="medio_respuesta"
                                value="SISTEMA"
                            >

                            <textarea
                                name="motivo_respuesta"
                                rows="2"
                                class="input-oneshop mt-3 w-full"
                                placeholder="Motivo del rechazo"
                                required
                            ></textarea>

                            <button
                                type="submit"
                                class="mt-3 w-full rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50"
                            >
                                Rechazar
                            </button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                <p class="font-semibold text-slate-700">
                    No hay autorizaciones pendientes.
                </p>
                <p class="mt-2 text-sm text-slate-500">
                    Las rebajas por debajo del mínimo aparecerán aquí cuando un vendedor solicite aprobación.
                </p>
            </div>
        @endforelse
    </section>

    <details class="card-oneshop">
        <summary class="cursor-pointer select-none px-6 py-5">
            <span class="font-semibold text-slate-950">
                Historial reciente
            </span>
            <span class="ml-2 text-sm text-slate-500">
                {{ $resueltas->count() }} decisiones
            </span>
        </summary>

        <div class="border-t border-slate-100 px-6 py-5">
            @if($resueltas->isEmpty())
                <p class="text-sm text-slate-500">
                    Todavía no existen solicitudes resueltas.
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Equipo</th>
                                <th class="px-4 py-3">Solicitado</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Respondido por</th>
                                <th class="px-4 py-3">Medio</th>
                                <th class="px-4 py-3">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($resueltas as $solicitud)
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-900">
                                        {{ $solicitud->precioEquipo?->equipo?->codigo_interno ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        Bs {{ number_format($solicitud->precio_solicitado, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $solicitud->estado === 'APROBADA' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                            {{ $solicitud->estado === 'APROBADA' ? 'Aprobada' : 'Rechazada' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $solicitud->respondidoPor?->name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $solicitud->medio_respuesta ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $solicitud->fecha_respuesta?->format('d/m/Y H:i') ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </details>
</div>
</x-layouts.oneshop>
