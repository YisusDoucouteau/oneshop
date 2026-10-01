@php
    $lote->load([
        'detalles.unidadesAdquiridas.producto.marca',
        'detalles.unidadesAdquiridas.almacenActual',
        'detalles.unidadesAdquiridas.moneda',
    ]);

    $unidades = $lote
        ->detalles
        ->pluck('unidadesAdquiridas')
        ->flatten()
        ->sortByDesc('created_at')
        ->values();

    $totalUnidades = $unidades->count();

    $unidadesActivas = $unidades
        ->where(
            'estado',
            '!=',
            \App\Models\UnidadAdquirida::ESTADO_ANULADA
        )
        ->count();

    $requierenServicio = $unidades
        ->where(
            'estado',
            '!=',
            \App\Models\UnidadAdquirida::ESTADO_ANULADA
        )
        ->where('requiere_servicio', true)
        ->count();
@endphp

<section
    id="equipos-registrados"
    class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm"
>
    {{-- HEADER --}}
    <div class="flex flex-col gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                <x-ui.icon name="monitor-check" size="18" />
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                    Recepción física
                </p>

                <h2 class="mt-0.5 text-lg font-bold text-slate-950">
                    Equipos registrados físicamente
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Unidades identificadas en Cochabamba. Todavía no forman parte del inventario definitivo.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">
                {{ $totalUnidades }}
                {{ $totalUnidades === 1 ? 'registro' : 'registros' }}
            </span>

            <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-oneshop-dark">
                {{ $unidadesActivas }}
                {{ $unidadesActivas === 1 ? 'activo' : 'activos' }}
            </span>

            @if($requierenServicio > 0)
                <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-800">
                    {{ $requierenServicio }}
                    {{ $requierenServicio === 1 ? 'requiere servicio' : 'requieren servicio' }}
                </span>
            @endif
        </div>
    </div>

    {{-- CONTROLES --}}
    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative w-full max-w-md">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-ui.icon name="search" size="16" />
            </span>

            <input
                id="buscarEquiposRegistrados"
                type="search"
                placeholder="Código, equipo, modelo, serial o estado..."
                autocomplete="off"
                class="w-full rounded-xl border border-slate-300 bg-white py-2.5 pl-9 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
            >
        </div>

        <div class="flex items-center justify-end gap-2">
            <span
                id="contadorEquiposRegistrados"
                class="min-w-[86px] text-right text-xs font-semibold text-slate-500"
            >
                0 de 0
            </span>

            <button
                id="paginaAnteriorEquipos"
                type="button"
                aria-label="Página anterior"
                class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-35"
            >
                <span aria-hidden="true" class="text-lg font-bold leading-none">‹</span>
            </button>

            <button
                id="paginaSiguienteEquipos"
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
        <table class="min-w-[1180px] w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Unidad
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Hardware
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Recepción
                    </th>

                    <th class="px-5 py-3.5 text-left text-xs font-bold uppercase tracking-wide text-slate-500">
                        Compra
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
                id="tablaEquiposRegistrados"
                class="divide-y divide-slate-100 bg-white"
            >
                @forelse($unidades as $unidad)
                    @php
                        $esAnulada =
                            $unidad->estado
                            ===
                            \App\Models\UnidadAdquirida::ESTADO_ANULADA;

                        $puedeModificarRecepcion = in_array(
                            $unidad->estado,
                            [
                                \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                                \App\Models\UnidadAdquirida::ESTADO_EN_REVISION,
                                \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION,
                            ],
                            true
                        );

                        $estadoMostrar = match ($unidad->estado) {
                            \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN =>
                                'Recibido en Cochabamba',

                            \App\Models\UnidadAdquirida::ESTADO_EN_REVISION =>
                                'En revisión',

                            \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION =>
                                'En preparación',

                            \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO =>
                                'Listo para envío',

                            'ENVIADA' =>
                                'Enviado a Oruro',

                            'RECIBIDA_ORURO' =>
                                'Recibido en Oruro',

                            'INCORPORADA' =>
                                'En inventario',

                            \App\Models\UnidadAdquirida::ESTADO_ANULADA =>
                                'Anulado',

                            default =>
                                ucfirst(
                                    strtolower(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $unidad->estado
                                        )
                                    )
                                ),
                        };

                        $etiquetaPrincipal = match ($unidad->estado) {
                            \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                            \App\Models\UnidadAdquirida::ESTADO_EN_REVISION,
                            \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION =>
                                'Preparar / Revisar',

                            \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO =>
                                'Ver preparación',

                            default =>
                                'Ver ficha',
                        };

                        $textoBusqueda = strtolower(
                            implode(
                                ' ',
                                array_filter([
                                    $unidad->codigo_trazabilidad,
                                    $unidad->producto?->marca?->nombre,
                                    $unidad->producto?->nombre,
                                    $unidad->producto?->modelo,
                                    $unidad->serial_fabricante,
                                    $unidad->procesador,
                                    $unidad->tarjeta_grafica,
                                    $unidad->grado_recibido,
                                    $estadoMostrar,
                                    $unidad->servicio_requerido,
                                ])
                            )
                        );
                    @endphp

                    <tr
                        data-fila-equipo
                        data-busqueda="{{ $textoBusqueda }}"
                        class="transition hover:bg-blue-50/30 {{ $esAnulada ? 'bg-slate-50/70 opacity-75' : '' }}"
                    >
                        {{-- UNIDAD --}}
                        <td class="px-5 py-4 align-top">
                            <a
                                href="{{ route('unidades-adquiridas.show', $unidad) }}"
                                class="font-bold text-slate-900 transition hover:text-oneshop-primary hover:underline"
                                title="Ver ficha completa de la unidad"
                            >
                                {{ $unidad->codigo_trazabilidad ?? 'Código pendiente' }}
                            </a>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $unidad->created_at?->format('d/m/Y H:i') }}
                            </p>

                            <div class="mt-3">
                                <p class="font-semibold text-slate-800">
                                    {{ $unidad->producto?->marca?->nombre }}
                                    {{ $unidad->producto?->nombre }}
                                </p>

                                <p class="mt-0.5 text-xs text-slate-500">
                                    {{ $unidad->producto?->modelo ?? 'Sin modelo' }}
                                </p>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @if($unidad->serial_fabricante)
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-600">
                                        Serial: {{ $unidad->serial_fabricante }}
                                    </span>
                                @endif

                                @if($unidad->grado_recibido)
                                    <span class="rounded-md border border-blue-100 bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-800">
                                        Grado {{ $unidad->grado_recibido }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- HARDWARE --}}
                        <td class="px-5 py-4 align-top">
                            <div class="space-y-1.5 text-xs text-slate-600">
                                <p>
                                    <span class="font-bold text-slate-700">CPU:</span>
                                    {{ $unidad->procesador ?: 'No registrado' }}
                                    @if($unidad->generacion_procesador)
                                        · {{ $unidad->generacion_procesador }}
                                    @endif
                                </p>

                                <p>
                                    <span class="font-bold text-slate-700">RAM:</span>

                                    @if($unidad->ram_gb !== null)
                                        {{ $unidad->ram_gb }} GB

                                        @if((int) $unidad->ram_gb === 0)
                                            <span class="font-bold text-amber-700">· faltante</span>
                                        @endif
                                    @else
                                        No registrada
                                    @endif
                                </p>

                                <p>
                                    <span class="font-bold text-slate-700">Disco:</span>

                                    @if($unidad->almacenamiento_gb !== null)
                                        {{ $unidad->almacenamiento_gb }} GB
                                        {{ $unidad->tipo_almacenamiento }}

                                        @if((int) $unidad->almacenamiento_gb === 0)
                                            <span class="font-bold text-amber-700">· faltante</span>
                                        @endif
                                    @else
                                        No registrado
                                    @endif
                                </p>

                                @if($unidad->tarjeta_grafica)
                                    <p>
                                        <span class="font-bold text-slate-700">GPU:</span>
                                        {{ $unidad->tarjeta_grafica }}
                                    </p>
                                @endif
                            </div>
                        </td>

                        {{-- RECEPCIÓN --}}
                        <td class="px-5 py-4 align-top">
                            <div class="space-y-2">
                                @if($unidad->tiene_cargador !== null)
                                    @if($unidad->tiene_cargador)
                                        <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                            Con cargador
                                        </span>
                                    @else
                                        <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-800">
                                            Sin cargador
                                        </span>
                                    @endif
                                @endif

                                @if($unidad->requiere_servicio)
                                    <div class="max-w-[240px] rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                        <p class="text-[11px] font-bold uppercase tracking-wide text-amber-800">
                                            Requiere servicio
                                        </p>

                                        <p class="mt-1 text-xs text-amber-900">
                                            {{ $unidad->servicio_requerido ?: 'Servicio pendiente de definir.' }}
                                        </p>
                                    </div>
                                @elseif(!$esAnulada)
                                    <p class="text-xs font-semibold text-slate-500">
                                        Sin servicio pendiente
                                    </p>
                                @endif
                            </div>
                        </td>

                        {{-- COMPRA --}}
                        <td class="px-5 py-4 align-top">
                            @if($unidad->precio_compra !== null)
                                <p class="font-bold text-slate-800">
                                    {{ number_format((float) $unidad->precio_compra, 2) }}
                                    {{ $unidad->moneda?->codigo }}
                                </p>

                                @if($unidad->precio_compra_bob !== null)
                                    <p class="mt-1 text-xs font-semibold text-slate-500">
                                        Bs {{ number_format((float) $unidad->precio_compra_bob, 2) }}
                                    </p>
                                @endif
                            @else
                                <span class="text-xs text-slate-400">
                                    Sin compra registrada
                                </span>
                            @endif
                        </td>

                        {{-- ESTADO --}}
                        <td class="px-5 py-4 align-top">
                            @switch($unidad->estado)
                                @case(\App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN)
                                    <span class="inline-flex rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-blue-800">
                                        {{ $estadoMostrar }}
                                    </span>
                                    @break

                                @case(\App\Models\UnidadAdquirida::ESTADO_EN_REVISION)
                                    <span class="inline-flex rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">
                                        {{ $estadoMostrar }}
                                    </span>
                                    @break

                                @case(\App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION)
                                    <span class="inline-flex rounded-full border border-violet-200 bg-violet-50 px-3 py-1 text-xs font-bold text-violet-800">
                                        {{ $estadoMostrar }}
                                    </span>
                                    @break

                                @case(\App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO)
                                    <span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                                        {{ $estadoMostrar }}
                                    </span>
                                    @break

                                @case(\App\Models\UnidadAdquirida::ESTADO_ANULADA)
                                    <div class="space-y-2">
                                        <span class="inline-flex rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-800">
                                            Anulado
                                        </span>

                                        @if($unidad->motivo_anulacion)
                                            <p class="max-w-[220px] text-xs text-red-700">
                                                {{ $unidad->motivo_anulacion }}
                                            </p>
                                        @endif
                                    </div>
                                    @break

                                @default
                                    <span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700">
                                        {{ $estadoMostrar }}
                                    </span>
                            @endswitch
                        </td>

                        {{-- ACCIONES --}}
                        <td class="px-5 py-4 align-top text-right">
                            <div class="flex flex-col items-end gap-2">
                                <a
                                    href="{{ route('unidades-adquiridas.show', $unidad) }}"
                                    class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-oneshop-dark transition hover:bg-blue-100"
                                >
                                    <x-ui.icon name="clipboard-check" size="14" />
                                    {{ $etiquetaPrincipal }}
                                </a>

                                <details class="relative">
                                    <summary class="list-none cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50">
                                        Gestionar ▾
                                    </summary>

                                    <div class="absolute right-0 z-30 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-left shadow-xl">
                                        <a
                                            href="{{ route('unidades-adquiridas.show', $unidad) }}"
                                            class="block px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        >
                                            Ver ficha completa
                                        </a>

                                        @if($puedeModificarRecepcion)
                                            <a
                                                href="{{ route('importaciones.unidades.editar', $unidad) }}"
                                                class="block px-3 py-2 text-xs font-semibold text-oneshop-dark hover:bg-blue-50"
                                            >
                                                Editar recepción
                                            </a>

                                            <button
                                                type="button"
                                                onclick="abrirModalAnularUnidad({{ $unidad->id }}, @js($unidad->codigo_trazabilidad ?? 'Unidad sin código'))"
                                                class="block w-full px-3 py-2 text-left text-xs font-semibold text-red-700 hover:bg-red-50"
                                            >
                                                Anular recepción
                                            </button>
                                        @elseif(!$esAnulada)
                                            <span class="block px-3 py-2 text-xs font-semibold text-slate-400">
                                                Recepción cerrada
                                            </span>
                                        @endif
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr id="filaVaciaEquipos">
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                <x-ui.icon name="monitor" size="20" />
                            </div>

                            <p class="mt-3 font-bold text-slate-700">
                                Sin equipos registrados
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Utiliza la acción “Registrar equipo recibido” de la cabecera del lote para comenzar la recepción física.
                            </p>
                        </td>
                    </tr>
                @endforelse

                @if($totalUnidades > 0)
                    <tr id="filaSinResultadosEquipos" class="hidden">
                        <td colspan="6" class="px-6 py-10 text-center">
                            <p class="font-bold text-slate-700">
                                No encontramos equipos
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Prueba con otro código, modelo, serial o estado.
                            </p>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</section>

{{-- MODAL ANULAR RECEPCIÓN --}}
<div
    id="modalAnularUnidad"
    class="fixed inset-0 z-50 hidden bg-slate-950/50 p-4 backdrop-blur-sm"
    onclick="cerrarModalAnularUnidadDesdeFondo(event)"
>
    <div class="flex min-h-full items-center justify-center">
        <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-red-100 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-red-100 bg-gradient-to-r from-red-50 via-white to-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-red-200 bg-white text-lg font-black text-red-700 shadow-sm">
                        !
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-red-700">
                            Recepción física
                        </p>

                        <h3 class="mt-0.5 text-xl font-bold text-slate-950">
                            Anular equipo recibido
                        </h3>

                        <p id="codigoUnidadAnular" class="mt-1 text-sm text-slate-500"></p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="cerrarModalAnularUnidad()"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-red-100 hover:text-slate-900"
                    aria-label="Cerrar"
                >
                    <x-ui.icon name="x" size="17" />
                </button>
            </div>

            <form id="formAnularUnidad" method="POST">
                @csrf
                @method('PATCH')

                <div class="p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                        <p class="text-sm font-bold text-amber-900">
                            El registro no se eliminará.
                        </p>

                        <p class="mt-1 text-xs leading-5 text-amber-800">
                            La unidad quedará anulada y se conservará en el historial para auditoría y trazabilidad.
                        </p>
                    </div>

                    <label
                        for="motivoAnulacionUnidad"
                        class="mt-5 block text-sm font-bold text-slate-700"
                    >
                        Motivo de anulación
                        <span class="text-red-700">*</span>
                    </label>

                    <textarea
                        id="motivoAnulacionUnidad"
                        name="motivo_anulacion"
                        required
                        rows="4"
                        placeholder="Ej. registro duplicado, error de recepción..."
                        class="mt-2 w-full resize-none rounded-xl border border-slate-300 bg-white p-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-red-400 focus:outline-none focus:ring-4 focus:ring-red-100"
                    ></textarea>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        onclick="cerrarModalAnularUnidad()"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl border border-red-200 bg-red-50 px-5 py-2.5 text-sm font-bold text-red-800 shadow-sm transition hover:bg-red-100"
                    >
                        Anular recepción
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
            'buscarEquiposRegistrados'
        );

    const filas =
        Array.from(
            document.querySelectorAll(
                '[data-fila-equipo]'
            )
        );

    const contador =
        document.getElementById(
            'contadorEquiposRegistrados'
        );

    const anterior =
        document.getElementById(
            'paginaAnteriorEquipos'
        );

    const siguiente =
        document.getElementById(
            'paginaSiguienteEquipos'
        );

    const filaSinResultados =
        document.getElementById(
            'filaSinResultadosEquipos'
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
            .slice(
                inicio,
                fin
            )
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
                    '#tablaEquiposRegistrados details[open]'
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


window.abrirModalAnularUnidad =
    function (
        id,
        codigo = ''
    ) {
        const modal =
            document.getElementById(
                'modalAnularUnidad'
            );

        const formulario =
            document.getElementById(
                'formAnularUnidad'
            );

        const codigoUnidad =
            document.getElementById(
                'codigoUnidadAnular'
            );

        if (
            !modal
            || !formulario
        ) {
            return;
        }

        formulario.reset();

        formulario.action =
            '/importaciones/unidades/'
            + id
            + '/anular';

        if (codigoUnidad) {
            codigoUnidad.textContent =
                codigo;
        }

        document
            .querySelectorAll(
                '#tablaEquiposRegistrados details[open]'
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

        window.setTimeout(
            () => {
                document
                    .getElementById(
                        'motivoAnulacionUnidad'
                    )
                    ?.focus();
            },
            80
        );
    };


window.cerrarModalAnularUnidad =
    function () {
        const modal =
            document.getElementById(
                'modalAnularUnidad'
            );

        const formulario =
            document.getElementById(
                'formAnularUnidad'
            );

        formulario?.reset();
        modal?.classList.add('hidden');

        document
            .body
            .classList
            .remove(
                'overflow-hidden'
            );
    };


window.cerrarModalAnularUnidadDesdeFondo =
    function (event) {
        const modal =
            document.getElementById(
                'modalAnularUnidad'
            );

        if (event.target === modal) {
            window
                .cerrarModalAnularUnidad();
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
                'modalAnularUnidad'
            );

        if (
            modal
            && !modal
                .classList
                .contains('hidden')
        ) {
            window
                .cerrarModalAnularUnidad();
        }
    }
);
</script>
@endpush
