<x-layouts.oneshop
    title="Importaciones | OneShop"
    page-title="Importaciones"
>

    <div class="space-y-6">

        {{-- Encabezado --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950">
                    Lotes de importación
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Control de compras, composición, recepción y procedencia de equipos importados.
                </p>
            </div>

            @if(auth()->user()->tienePermiso('importacion.gestionar'))
                <a
                    href="{{ route('importaciones.create') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    + Nuevo lote
                </a>
            @endif

        </div>


        {{-- Métricas --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Total de lotes
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-950">
                    {{ $resumen['total'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Abiertos
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-950">
                    {{ $resumen['abiertos'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Recepción parcial
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-950">
                    {{ $resumen['parciales'] }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Recibidos
                </p>

                <p class="mt-2 text-3xl font-bold text-slate-950">
                    {{ $resumen['recibidos'] }}
                </p>
            </div>

        </div>


        {{-- Filtros --}}
        <form
            method="GET"
            action="{{ route('importaciones.index') }}"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
        >

            <div class="grid gap-4 lg:grid-cols-[1fr_220px_auto]">

                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Buscar
                    </label>

                    <input
                        type="text"
                        name="buscar"
                        value="{{ $buscar }}"
                        placeholder="Código, proveedor, referencia u origen..."
                        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Estado
                    </label>

                    <select
                        name="estado"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                    >
                        <option value="">
                            Todos
                        </option>

                        <option value="ABIERTO" @selected($estado === 'ABIERTO')>
                            Abierto
                        </option>

                        <option value="RECEPCION_PARCIAL" @selected($estado === 'RECEPCION_PARCIAL')>
                            Recepción parcial
                        </option>

                        <option value="RECIBIDO" @selected($estado === 'RECIBIDO')>
                            Recibido
                        </option>

                        <option value="CERRADO" @selected($estado === 'CERRADO')>
                            Cerrado
                        </option>
                    </select>
                </div>


                <div class="flex items-end gap-2">

                    <button
                        type="submit"
                        class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white"
                    >
                        Filtrar
                    </button>

                    <a
                        href="{{ route('importaciones.index') }}"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-600"
                    >
                        Limpiar
                    </a>

                </div>

            </div>

        </form>


        {{-- Tabla --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Lote
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Proveedor
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Origen
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Recepción
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Estado
                            </th>

                            <th class="px-6 py-4"></th>
                        </tr>
                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($lotes as $lote)

                            @php
                                $esperadas = (int) ($lote->cantidad_esperada_total ?? 0);
                                // Recepción física real en Cochabamba. No usamos
                                // detalles_lotes.cantidad_recibida porque ese campo
                                // pertenece a la etapa posterior en Oruro.
                                $recibidas = (int) ($lote->cantidad_recibida_fisica_total ?? 0);

                                $porcentaje = $esperadas > 0
                                    ? min(100, round(($recibidas / $esperadas) * 100))
                                    : 0;
                            @endphp

                            <tr class="transition hover:bg-slate-50/70">

                                <td class="px-6 py-5">
                                    <p class="font-semibold text-slate-950">
                                        {{ $lote->codigo }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $lote->referencia_compra ?: 'Sin referencia' }}
                                    </p>
                                </td>


                                <td class="px-6 py-5 text-sm text-slate-700">
                                    {{ $lote->proveedor?->nombre ?? 'Sin proveedor' }}
                                </td>


                                <td class="px-6 py-5 text-sm text-slate-700">
                                    {{ $lote->origen ?: 'No especificado' }}
                                </td>


                                <td class="px-6 py-5">

                                    <div class="min-w-36">

                                        <div class="flex items-center justify-between text-xs">
                                            <span class="font-medium text-slate-700">
                                                {{ $recibidas }} / {{ $esperadas }}
                                            </span>

                                            <span class="text-slate-500">
                                                {{ $porcentaje }}%
                                            </span>
                                        </div>

                                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">

                                            <div
                                                class="h-full rounded-full bg-slate-900"
                                                style="width: {{ $porcentaje }}%"
                                            ></div>

                                        </div>

                                    </div>

                                </td>


                                <td class="px-6 py-5">

                                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ str_replace('_', ' ', $lote->estado) }}
                                    </span>

                                </td>


                                <td class="px-6 py-5 text-right">

                                    <a
                                        href="{{ route('importaciones.show', $lote) }}"
                                        class="text-sm font-semibold text-slate-900 hover:underline"
                                    >
                                        Ver →
                                    </a>

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-16 text-center"
                                >
                                    <p class="font-semibold text-slate-700">
                                        No existen lotes para mostrar.
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Crea el primer lote de importación para comenzar.
                                    </p>
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

            @if($lotes->hasPages())
                <div class="border-t border-slate-200 px-6 py-4">
                    {{ $lotes->links() }}
                </div>
            @endif

        </div>

    </div>

</x-layouts.oneshop>