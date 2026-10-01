@php
    $costosLote = $lote
        ->costos
        ->sortByDesc('fecha_costo')
        ->values();

    $costosActivos = $costosLote
        ->where('estado', '!=', 'ANULADO');

    $totalCostosActivos = $costosActivos->count();

    $totalCostosBob = $costosActivos->sum(
        fn ($costo) => (float) $costo->monto_bob
    );
@endphp

<section
    id="costos-importacion"
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
                    Control financiero
                </p>

                <h2 class="mt-0.5 text-lg font-bold text-slate-950">
                    Costos de importación
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Gastos asociados al lote para control financiero. Se mantienen separados y no aumentan el costo individual de los equipos.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">
                {{ $totalCostosActivos }}
                {{ $totalCostosActivos === 1 ? 'costo activo' : 'costos activos' }}
            </span>

            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-oneshop-dark">
                Bs {{ number_format($totalCostosBob, 2) }}
            </span>
        </div>
    </div>

    {{-- CONTROLES --}}
    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full max-w-md">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-ui.icon name="search" size="16" />
            </span>

            <input
                id="buscarCostosLote"
                type="search"
                autocomplete="off"
                placeholder="Tipo, referencia, moneda o estado..."
                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
            >
        </div>

        <div class="flex items-center justify-end gap-2">
            <span
                id="contadorCostosLote"
                class="min-w-[86px] text-right text-xs font-semibold text-slate-500"
            >
                0 de 0
            </span>

            <button
                id="paginaAnteriorCostos"
                type="button"
                aria-label="Página anterior"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-35"
            >
                <span aria-hidden="true" class="text-lg font-bold leading-none">‹</span>
            </button>

            <button
                id="paginaSiguienteCostos"
                type="button"
                aria-label="Página siguiente"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-35"
            >
                <span aria-hidden="true" class="text-lg font-bold leading-none">›</span>
            </button>
        </div>
    </div>

    {{-- TABLA --}}
    <div class="overflow-x-auto">
        <table class="min-w-[900px] w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Costo
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Importe
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Equivalente Bs
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Fecha
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Estado
                    </th>

                    <th class="px-5 py-3.5 text-right text-xs font-bold uppercase tracking-wide text-slate-500">
                        Acción
                    </th>
                </tr>
            </thead>

            <tbody
                id="tablaCostosLote"
                class="divide-y divide-slate-100 bg-white"
            >
                @forelse($costosLote as $costo)
                    @php
                        $esAnulado = $costo->estado === 'ANULADO';

                        $textoBusqueda = strtolower(
                            implode(
                                ' ',
                                array_filter([
                                    $costo->tipoCosto?->nombre,
                                    $costo->referencia,
                                    $costo->observacion,
                                    $costo->moneda?->codigo,
                                    $costo->estado,
                                    $costo->fecha_costo?->format('d/m/Y'),
                                ])
                            )
                        );
                    @endphp

                    <tr
                        data-fila-costo
                        data-busqueda="{{ $textoBusqueda }}"
                        class="transition hover:bg-blue-50/30 {{ $esAnulado ? 'bg-slate-50/70 opacity-75' : '' }}"
                    >
                        {{-- COSTO --}}
                        <td class="px-5 py-4 align-top">
                            <p class="font-bold text-slate-900">
                                {{ $costo->tipoCosto?->nombre ?? 'Sin tipo' }}
                            </p>

                            @if($costo->referencia)
                                <p class="mt-1 text-xs text-slate-500">
                                    Ref: {{ $costo->referencia }}
                                </p>
                            @endif

                            @if($costo->observacion)
                                <p class="mt-2 max-w-xs text-xs leading-5 text-slate-500">
                                    {{ $costo->observacion }}
                                </p>
                            @endif
                        </td>

                        {{-- IMPORTE --}}
                        <td class="px-5 py-4 align-top">
                            <p class="font-bold text-slate-800">
                                {{ number_format((float) $costo->monto_origen, 2) }}
                                {{ $costo->moneda?->codigo }}
                            </p>

                            @if($costo->tipoCambio?->valor)
                                <p class="mt-1 text-xs text-slate-500">
                                    TC aplicado:
                                    {{ number_format((float) $costo->tipoCambio->valor, 6) }}
                                </p>
                            @endif
                        </td>

                        {{-- BOLIVIANOS --}}
                        <td class="px-5 py-4 align-top">
                            <p class="font-bold text-slate-800">
                                Bs {{ number_format((float) $costo->monto_bob, 2) }}
                            </p>
                        </td>

                        {{-- FECHA --}}
                        <td class="px-5 py-4 align-top text-sm text-slate-600">
                            {{ $costo->fecha_costo?->format('d/m/Y') ?? 'Sin fecha' }}
                        </td>

                        {{-- ESTADO --}}
                        <td class="px-5 py-4 align-top">
                            @if($esAnulado)
                                <div class="space-y-2">
                                    <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-800">
                                        Anulado
                                    </span>

                                    @if($costo->motivo_anulacion)
                                        <p class="max-w-[220px] text-xs text-red-700">
                                            {{ $costo->motivo_anulacion }}
                                        </p>
                                    @endif
                                </div>
                            @else
                                <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                                    Activo
                                </span>
                            @endif
                        </td>

                        {{-- ACCIONES --}}
                        <td class="px-5 py-4 align-top text-right">
                            @if(!$esAnulado)
                                <details class="relative inline-block text-left">
                                    <summary class="list-none cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Gestionar ▾
                                    </summary>

                                    <div class="absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                                        <button
                                            type="button"
                                            data-costo-id="{{ $costo->id }}"
                                            data-tipo="{{ $costo->tipo_costo_id }}"
                                            data-moneda="{{ $costo->moneda_id }}"
                                            data-monto="{{ $costo->monto_origen }}"
                                            data-fecha="{{ $costo->fecha_costo?->format('Y-m-d') }}"
                                            data-referencia="{{ $costo->referencia }}"
                                            data-observacion="{{ $costo->observacion }}"
                                            data-tipo-cambio="{{ $costo->tipoCambio?->valor }}"
                                            onclick="editarCostoDesdeBoton(this)"
                                            class="block w-full px-3 py-2 text-left text-xs font-semibold text-oneshop-dark hover:bg-blue-50"
                                        >
                                            Editar costo
                                        </button>

                                        <button
                                            type="button"
                                            data-costo-id="{{ $costo->id }}"
                                            data-costo-nombre="{{ $costo->tipoCosto?->nombre ?? 'Costo' }}"
                                            onclick="abrirModalAnularCostoDesdeBoton(this)"
                                            class="block w-full px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50"
                                        >
                                            Anular costo
                                        </button>
                                    </div>
                                </details>
                            @else
                                <span class="text-xs font-semibold text-slate-400">
                                    Sin acciones
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr id="filaVaciaCostos">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-sm font-black text-slate-500">
                                Bs
                            </div>

                            <p class="mt-3 font-bold text-slate-700">
                                Sin costos registrados
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Utiliza “Registrar costo” en Acciones del lote para añadir el primer gasto.
                            </p>
                        </td>
                    </tr>
                @endforelse

                @if($costosLote->isNotEmpty())
                    <tr id="filaSinResultadosCostos" class="hidden">
                        <td colspan="6" class="px-6 py-10 text-center">
                            <p class="font-bold text-slate-700">
                                No encontramos costos
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Prueba con otro tipo, referencia, moneda o estado.
                            </p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</section>

{{-- MODAL CONFIRMAR ANULACIÓN --}}
<div
    id="modalAnularCosto"
    class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm"
    onclick="cerrarModalAnularCostoDesdeFondo(event)"
>
    <div class="flex min-h-full items-center justify-center">
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-red-100 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-red-200 bg-white text-lg font-black text-red-700 shadow-sm">
                        !
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-red-700">
                            Control financiero
                        </p>

                        <h3 class="mt-0.5 text-xl font-bold text-slate-950">
                            Anular costo
                        </h3>

                        <p id="nombreCostoAnular" class="mt-1 text-sm text-slate-500"></p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="cerrarModalAnularCosto()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-red-100 hover:text-slate-900"
                    aria-label="Cerrar"
                >
                    <x-ui.icon name="x" size="17" />
                </button>
            </div>

            <form id="formAnularCosto" method="POST">
                @csrf
                @method('PATCH')

                <input
                    type="hidden"
                    name="motivo_anulacion"
                    value="Costo anulado manualmente"
                >

                <div class="p-6">
                    <p class="text-sm leading-6 text-slate-600">
                        El costo dejará de considerarse activo, pero el registro se conservará para auditoría.
                    </p>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="cerrarModalAnularCosto()"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-bold text-red-800 shadow-sm transition hover:bg-red-100"
                    >
                        Anular costo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const buscador =
        document.getElementById(
            'buscarCostosLote'
        );

    const filas =
        Array.from(
            document.querySelectorAll(
                '[data-fila-costo]'
            )
        );

    const contador =
        document.getElementById(
            'contadorCostosLote'
        );

    const anterior =
        document.getElementById(
            'paginaAnteriorCostos'
        );

    const siguiente =
        document.getElementById(
            'paginaSiguienteCostos'
        );

    const filaSinResultados =
        document.getElementById(
            'filaSinResultadosCostos'
        );

    const elementosPorPagina = 10;
    let paginaActual = 1;
    let filtradas = filas;

    function normalizar(texto) {
        return (texto ?? '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function renderizarTabla() {
        const total = filtradas.length;

        const totalPaginas =
            Math.max(
                1,
                Math.ceil(
                    total / elementosPorPagina
                )
            );

        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }

        const inicio =
            (paginaActual - 1)
            * elementosPorPagina;

        const fin =
            Math.min(
                inicio + elementosPorPagina,
                total
            );

        filas.forEach(
            (fila) =>
                fila.classList.add('hidden')
        );

        filtradas
            .slice(inicio, fin)
            .forEach(
                (fila) =>
                    fila.classList.remove('hidden')
            );

        if (contador) {
            contador.textContent =
                total === 0
                    ? '0 de 0'
                    : `${inicio + 1}–${fin} de ${total}`;
        }

        if (anterior) {
            anterior.disabled =
                paginaActual <= 1
                || total === 0;
        }

        if (siguiente) {
            siguiente.disabled =
                paginaActual >= totalPaginas
                || total === 0;
        }

        filaSinResultados
            ?.classList
            .toggle(
                'hidden',
                total !== 0
            );
    }

    function filtrar() {
        const consulta =
            normalizar(
                buscador?.value
            );

        filtradas =
            filas.filter(
                (fila) =>
                    normalizar(
                        fila.dataset.busqueda
                    )
                    .includes(
                        consulta
                    )
            );

        paginaActual = 1;
        renderizarTabla();
    }

    buscador
        ?.addEventListener(
            'input',
            filtrar
        );

    anterior
        ?.addEventListener(
            'click',
            function () {
                if (paginaActual <= 1) {
                    return;
                }

                paginaActual--;
                renderizarTabla();
            }
        );

    siguiente
        ?.addEventListener(
            'click',
            function () {
                const totalPaginas =
                    Math.ceil(
                        filtradas.length
                        / elementosPorPagina
                    );

                if (
                    paginaActual
                    >= totalPaginas
                ) {
                    return;
                }

                paginaActual++;
                renderizarTabla();
            }
        );

    document.addEventListener(
        'click',
        function (event) {
            document
                .querySelectorAll(
                    '#tablaCostosLote details[open]'
                )
                .forEach(
                    (menu) => {
                        if (
                            !menu.contains(
                                event.target
                            )
                        ) {
                            menu.removeAttribute(
                                'open'
                            );
                        }
                    }
                );
        }
    );

    renderizarTabla();
})();


window.abrirModalAnularCostoDesdeBoton =
    function (boton) {
        window.abrirModalAnularCosto(
            boton.dataset.costoId,
            boton.dataset.costoNombre || 'Costo'
        );
    };


window.abrirModalAnularCosto =
    function (
        id,
        nombre = 'Costo'
    ) {
        const modal =
            document.getElementById(
                'modalAnularCosto'
            );

        const formulario =
            document.getElementById(
                'formAnularCosto'
            );

        const nombreCosto =
            document.getElementById(
                'nombreCostoAnular'
            );

        if (
            !modal
            || !formulario
        ) {
            return;
        }

        formulario.action =
            '/importaciones/costos/'
            + id
            + '/anular';

        if (nombreCosto) {
            nombreCosto.textContent =
                nombre;
        }

        document
            .querySelectorAll(
                '#tablaCostosLote details[open]'
            )
            .forEach(
                (menu) =>
                    menu.removeAttribute(
                        'open'
                    )
            );

        modal.classList.remove(
            'hidden'
        );

        document
            .body
            .classList
            .add(
                'overflow-hidden'
            );
    };


window.cerrarModalAnularCosto =
    function () {
        document
            .getElementById(
                'modalAnularCosto'
            )
            ?.classList
            .add('hidden');

        document
            .body
            .classList
            .remove(
                'overflow-hidden'
            );
    };


window.cerrarModalAnularCostoDesdeFondo =
    function (event) {
        const modal =
            document.getElementById(
                'modalAnularCosto'
            );

        if (event.target === modal) {
            window
                .cerrarModalAnularCosto();
        }
    };


document.addEventListener(
    'keydown',
    function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        const modal =
            document.getElementById(
                'modalAnularCosto'
            );

        if (
            modal
            && !modal
                .classList
                .contains('hidden')
        ) {
            window
                .cerrarModalAnularCosto();
        }
    }
);
</script>
@endpush
