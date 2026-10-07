<x-layouts.oneshop
    title="Garantías y postventa | OneShop"
    page-title="Garantías y postventa"
>

<div class="space-y-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Garantías y postventa
            </h1>

            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Consulta garantías, localiza equipos vendidos y da seguimiento a los casos de postventa desde un solo lugar.
            </p>
        </div>

        <a
            href="{{ route('ventas.index') }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Ver ventas
        </a>

    </div>


    @if(session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
            {{ session('success') }}
        </div>

    @endif


    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-500">
                Garantías registradas
            </p>

            <p class="mt-2 text-3xl font-black text-slate-900">
                {{ $resumen['total'] }}
            </p>
        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-emerald-700">
                Garantías vigentes
            </p>

            <p class="mt-2 text-3xl font-black text-emerald-800">
                {{ $resumen['vigentes'] }}
            </p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">
                Casos activos
            </p>

            <p class="mt-2 text-3xl font-black text-amber-800">
                {{ $resumen['casos_activos'] }}
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 shadow-sm">
            <p class="text-sm font-medium text-slate-600">
                Garantías vencidas
            </p>

            <p class="mt-2 text-3xl font-black text-slate-800">
                {{ $resumen['vencidas'] }}
            </p>
        </div>

    </div>


    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            id="garantias_filtros"
            method="GET"
            action="{{ route('garantias.index') }}"
            data-auto-filter
            class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_220px_220px_auto]"
        >

            <div>

                <label
                    for="buscar"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Buscar
                </label>

                <div class="relative mt-2">

                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-ui.icon name="search" size="18"/>
                    </span>

                    <input
                        id="buscar"
                        name="buscar"
                        value="{{ $busqueda }}"
                        class="w-full rounded-xl border-slate-300 pl-10 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        placeholder="N° equipo, serial, cliente, teléfono, venta, garantía o caso"
                        autocomplete="off"
                        data-auto-submit
                    >

                </div>

            </div>


            <div>

                <label
                    for="estado"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Garantía
                </label>

                <select
                    id="estado"
                    name="estado"
                    data-auto-submit
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">
                        Todas
                    </option>

                    <option value="vigente" {{ $estado === 'vigente' ? 'selected' : '' }}>
                        Vigente
                    </option>

                    <option value="vencida" {{ $estado === 'vencida' ? 'selected' : '' }}>
                        Vencida
                    </option>

                    <option value="anulada" {{ $estado === 'anulada' ? 'selected' : '' }}>
                        Anulada
                    </option>
                </select>

            </div>


            <div>

                <label
                    for="caso"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Postventa
                </label>

                <select
                    id="caso"
                    name="caso"
                    data-auto-submit
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                >
                    <option value="">
                        Todos
                    </option>

                    <option value="activo" {{ $caso === 'activo' ? 'selected' : '' }}>
                        Con caso activo
                    </option>

                    <option value="cerrado" {{ $caso === 'cerrado' ? 'selected' : '' }}>
                        Con caso cerrado
                    </option>

                    <option value="sin_caso" {{ $caso === 'sin_caso' ? 'selected' : '' }}>
                        Sin caso
                    </option>
                </select>

            </div>


            <div class="flex items-end gap-2">

                <div class="flex min-h-10 items-center text-xs font-medium text-slate-400" id="garantias_filtros_estado">
                    Filtrado automático
                </div>

                @if($busqueda !== '' || $estado !== '' || $caso !== '')

                    <a
                        href="{{ route('garantias.index') }}"
                        class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50"
                    >
                        Limpiar filtros
                    </a>

                @endif

            </div>

        </form>

    </div>


    <x-ui.card padding="false">

        <div class="overflow-x-auto">

            <table class="w-full min-w-[980px] text-sm">

                <thead class="border-b bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">

                    <tr>
                        <th class="px-5 py-4">
                            Equipo
                        </th>

                        <th class="px-5 py-4">
                            Cliente
                        </th>

                        <th class="px-5 py-4">
                            Garantía
                        </th>

                        <th class="px-5 py-4">
                            Vigencia
                        </th>

                        <th class="px-5 py-4">
                            Postventa
                        </th>

                        <th class="px-5 py-4">
                            <span class="sr-only">
                                Acción
                            </span>
                        </th>
                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($garantias as $garantia)

                        @php
                            $detalle = $garantia->detalleVenta;
                            $equipo = $detalle?->equipo;
                            $venta = $detalle?->venta;

                            $clienteNombre =
                                $venta?->cliente_nombre_snapshot
                                ?: $venta?->cliente?->nombre_completo
                                ?: 'Cliente no disponible';

                            $clienteTelefono =
                                $venta?->cliente_telefono_snapshot
                                ?: $venta?->cliente?->telefono;

                            $garantiaVigente =
                                $garantia->estado === 'VIGENTE'
                                && $garantia->fecha_fin
                                && $garantia->fecha_fin->isFuture();

                            $estadoGarantiaTexto =
                                $garantia->estado === 'ANULADA'
                                    ? 'Anulada'
                                    : ($garantiaVigente ? 'Vigente' : 'Vencida');

                            $estadoGarantiaColor =
                                $garantia->estado === 'ANULADA'
                                    ? 'red'
                                    : ($garantiaVigente ? 'green' : 'gray');

                            $casoActivo = $garantia->casosGarantia->first(
                                fn ($item) => in_array(
                                    strtoupper((string) $item->estado),
                                    ['ABIERTO', 'DIAGNOSTICADO', 'EN_PROCESO'],
                                    true
                                )
                            );

                            $ultimoCaso = $garantia->casosGarantia->first();

                            $estadoCasoTexto = match (
                                strtoupper((string) ($casoActivo?->estado ?? $ultimoCaso?->estado))
                            ) {
                                'ABIERTO' => 'Abierto',
                                'DIAGNOSTICADO' => 'Diagnosticado',
                                'EN_PROCESO' => 'En proceso',
                                'CERRADO' => 'Cerrado',
                                default => null,
                            };
                        @endphp

                        <tr class="align-top hover:bg-slate-50">

                            <td class="px-5 py-4">

                                <p class="font-bold text-slate-900">
                                    {{ $equipo?->codigo_interno ?? 'Sin N° de equipo' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        trim(
                                            ($equipo?->producto?->marca?->nombre ?? '')
                                            . ' '
                                            . ($equipo?->producto?->modelo ?? '')
                                        )
                                        ?: ($equipo?->producto?->nombre ?? 'Equipo')
                                    }}
                                </p>

                                @if($equipo?->serial_fabricante)
                                    <p class="mt-1 text-xs text-slate-400">
                                        Serial: {{ $equipo->serial_fabricante }}
                                    </p>
                                @endif

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-800">
                                    {{ $clienteNombre }}
                                </p>

                                @if($clienteTelefono)
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $clienteTelefono }}
                                    </p>
                                @endif

                                @if($venta)
                                    <p class="mt-1 text-xs text-slate-400">
                                        Venta {{ $venta->numero }}
                                    </p>
                                @endif

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-800">
                                    {{ $garantia->numero }}
                                </p>

                                <div class="mt-2">
                                    <x-ui.badge :color="$estadoGarantiaColor">
                                        {{ $estadoGarantiaTexto }}
                                    </x-ui.badge>
                                </div>

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $garantia->fecha_fin?->format('d/m/Y') ?? 'Sin fecha' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    Inicio:
                                    {{ $garantia->fecha_inicio?->format('d/m/Y') ?? '—' }}
                                </p>

                            </td>


                            <td class="px-5 py-4">

                                @if($casoActivo)

                                    <p class="font-semibold text-amber-800">
                                        {{ $casoActivo->numero }}
                                    </p>

                                    <p class="mt-1 text-xs text-amber-700">
                                        {{ $estadoCasoTexto }}
                                    </p>

                                @elseif($ultimoCaso)

                                    <p class="font-semibold text-slate-700">
                                        {{ $ultimoCaso->numero }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $estadoCasoTexto ?? 'Caso registrado' }}
                                    </p>

                                @else

                                    <span class="text-slate-400">
                                        Sin caso
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-right">

                                @if($casoActivo)

                                    <a
                                        href="{{ route('garantias.casos.show', $casoActivo) }}"
                                        class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
                                    >
                                        Ver caso
                                    </a>

                                @else

                                    <a
                                        href="{{ route('garantias.show', $garantia) }}"
                                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Ver garantía
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-14 text-center"
                            >

                                <div class="mx-auto max-w-md">

                                    <p class="font-semibold text-slate-700">
                                        No se encontraron garantías
                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">
                                        Prueba con otro N° de equipo, serial, cliente, teléfono o estado.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($garantias->hasPages())

            <div class="border-t border-slate-100 p-5">
                {{ $garantias->links() }}
            </div>

        @endif

    </x-ui.card>

</div>

@once
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-auto-filter]');

        if (!form) {
            return;
        }

        const search = form.querySelector('input[name="buscar"]');
        const selects = form.querySelectorAll('select[data-auto-submit]');
        const status = document.getElementById('garantias_filtros_estado');
        let timer = null;

        const enviar = () => {
            if (status) {
                status.textContent = 'Actualizando…';
            }

            form.requestSubmit();
        };

        search?.addEventListener('input', () => {
            window.clearTimeout(timer);

            if (status) {
                status.textContent = 'Esperando…';
            }

            timer = window.setTimeout(enviar, 350);
        });

        selects.forEach((select) => {
            select.addEventListener('change', enviar);
        });
    });
</script>
@endonce

</x-layouts.oneshop>
