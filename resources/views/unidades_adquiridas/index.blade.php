<x-layouts.oneshop
    title="Unidades adquiridas | OneShop"
    page-title="Unidades adquiridas"
>

@php
    $colorEstado = function (?string $estado): string {
        return match ($estado) {
            \App\Models\UnidadAdquirida::ESTADO_PENDIENTE_LLEGADA => 'gray',
            \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN => 'blue',
            \App\Models\UnidadAdquirida::ESTADO_EN_REVISION => 'yellow',
            \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION => 'yellow',
            \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO => 'blue',
            \App\Models\UnidadAdquirida::ESTADO_ENVIADA => 'blue',
            \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORURO => 'green',
            \App\Models\UnidadAdquirida::ESTADO_INCORPORADA => 'green',
            \App\Models\UnidadAdquirida::ESTADO_ANULADA => 'red',
            default => 'gray',
        };
    };

    $nombreEstado = function (?string $estado): string {
        return match ($estado) {
            'PENDIENTE_LLEGADA' => 'Pendiente de llegada',
            'RECIBIDA_ORIGEN' => 'Recibida en origen',
            'EN_REVISION' => 'En revisión',
            'EN_PREPARACION' => 'En preparación',
            'LISTA_ENVIO' => 'Lista para envío',
            'ENVIADA' => 'Enviada',
            'RECIBIDA_ORURO' => 'Recibida en Oruro',
            'INCORPORADA' => 'Incorporada',
            'ANULADA' => 'Anulada',
            default => str_replace('_', ' ', $estado ?? 'Sin estado'),
        };
    };
@endphp

@php
    $registrosFiltro = $unidades
        ->map(function ($unidad) {
            return [
                'id' => (int) $unidad->id,
                'estado' => (string) ($unidad->estado ?? ''),
                'texto' => implode(' ', array_filter([
                    $unidad->codigo_trazabilidad,
                    $unidad->serial_fabricante,
                    $unidad->nombre_equipo,
                    $unidad->modelo_equipo,
                    $unidad->producto?->nombre,
                    $unidad->producto?->modelo,
                    $unidad->producto?->marca?->nombre,
                    $unidad->almacenActual?->nombre,
                ])),
            ];
        })
        ->values();
@endphp

<div
    x-data="{
        buscar: @js((string) request('buscar', '')),
        estado: @js((string) request('estado', '')),
        registros: @js($registrosFiltro),
        normalizar(valor) {
            return String(valor ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();
        },
        visible(registro) {
            const termino = this.normalizar(this.buscar);
            return (
                (termino === '' || this.normalizar(registro.texto).includes(termino))
                && (this.estado === '' || String(registro.estado) === String(this.estado))
            );
        },
        visiblePorId(id) {
            const registro = this.registros.find(item => Number(item.id) === Number(id));
            return registro ? this.visible(registro) : false;
        },
        get totalFiltrado() {
            return this.registros.filter(registro => this.visible(registro)).length;
        },
        limpiar() {
            this.buscar = '';
            this.estado = '';
        }
    }"
    class="space-y-6"
>

    {{-- Encabezado --}}
    <div
        class="
            flex
            flex-col
            gap-4
            lg:flex-row
            lg:items-center
            lg:justify-between
        "
    >

        <div>
            <div class="flex items-center gap-3">

                <div
                    class="
                        flex
                        h-11
                        w-11
                        items-center
                        justify-center
                        rounded-xl
                        bg-oneshop-light
                        text-oneshop-primary
                    "
                >
                    <x-ui.icon name="package" size="22"/>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-slate-900">
                        Unidades adquiridas
                    </h1>

                    <p class="mt-1 text-sm text-slate-500">
                        Seguimiento de equipos desde su adquisición hasta la incorporación al inventario.
                    </p>
                </div>

            </div>
        </div>


        @if(auth()->user()?->tienePermiso('importacion.gestionar'))

            <a
                href="{{ route('unidades-adquiridas.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    border
                    border-oneshop-primary
                    bg-oneshop-light
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-oneshop-dark
                    transition
                    hover:bg-blue-100
                "
            >
                <x-ui.icon name="plus" size="18"/>

                Registrar unidad
            </a>

        @endif

    </div>


    {{-- Mensaje --}}
    @if(session('success'))

        <div
            class="
                flex
                items-start
                gap-3
                rounded-xl
                border
                border-green-200
                bg-green-50
                p-4
                text-green-800
            "
        >

            <x-ui.icon
                name="check"
                size="20"
                class="mt-0.5 shrink-0"
            />

            <span class="text-sm font-medium">
                {{ session('success') }}
            </span>

        </div>

    @endif


    {{-- Filtros --}}
    <x-ui.card>

        <div
            class="
                grid
                grid-cols-1
                gap-4
                md:grid-cols-12
                md:items-end
            "
        >

            {{-- Buscar --}}
            <div class="md:col-span-7">

                <label
                    for="buscar"
                    class="
                        mb-2
                        block
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wide
                        text-slate-500
                    "
                >
                    Buscar unidad
                </label>

                <div class="relative">

                    <div
                        class="
                            pointer-events-none
                            absolute
                            inset-y-0
                            left-0
                            flex
                            items-center
                            pl-4
                            text-slate-400
                        "
                    >
                        <x-ui.icon name="search" size="18"/>
                    </div>

                    <input
                        id="buscar"
                        type="text"
                        x-model.debounce.150ms="buscar"
                        placeholder="Código, serial o nombre del equipo"
                        class="
                            input-oneshop
                            w-full
                            pl-11
                            pr-4
                        "
                    >

                </div>

            </div>


            {{-- Estado --}}
            <div class="md:col-span-3">

                <label
                    for="estado"
                    class="
                        mb-2
                        block
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wide
                        text-slate-500
                    "
                >
                    Estado
                </label>

                <select
                    id="estado"
                    x-model="estado"
                    class="input-oneshop w-full"
                >

                    <option value="">
                        Todos los estados
                    </option>

                    @foreach($estados as $estado)

                        <option
                            value="{{ $estado }}"
                        >
                            {{ $nombreEstado($estado) }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- Acciones de filtro --}}
            <div class="flex md:col-span-2">
                <button
                    type="button"
                    @click="limpiar()"
                    :disabled="buscar === '' && estado === ''"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <x-ui.icon name="x" size="17"/>
                    Limpiar
                </button>
            </div>

        </div>

    </x-ui.card>


    {{-- Tabla --}}
    <x-ui.card padding="false">

        {{-- Cabecera tabla --}}
        <div
            class="
                flex
                flex-col
                gap-2
                border-b
                border-slate-200
                px-5
                py-4
                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <div>
                <h2 class="font-semibold text-slate-900">
                    Registro de unidades
                </h2>

                <p class="text-xs text-slate-500">
                    <span x-text="totalFiltrado"></span>
                    de {{ $unidades->count() }} unidades visibles
                </p>
            </div>


            <span
                x-show="buscar !== '' || estado !== ''"
                class="text-xs font-medium text-oneshop-primary"
            >
                Filtro local activo
            </span>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr
                        class="
                            border-b
                            border-slate-200
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-slate-500
                        "
                    >

                        <th class="px-5 py-4 text-left">
                            Unidad
                        </th>

                        <th class="px-5 py-4 text-left">
                            Equipo
                        </th>

                        <th class="px-5 py-4 text-left">
                            Procedencia
                        </th>

                        <th class="px-5 py-4 text-left">
                            Estado
                        </th>

                        <th class="px-5 py-4 text-left">
                            Ubicación
                        </th>

                        <th class="px-5 py-4 text-right">
                            Acción
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach($unidades as $unidad)

                        <tr
                            x-show="visiblePorId({{ $unidad->id }})"
                            class="transition hover:bg-slate-50/80"
                        >

                            {{-- Unidad --}}
                            <td class="px-5 py-4 align-middle">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-xl
                                            bg-oneshop-soft
                                            text-oneshop-primary
                                        "
                                    >
                                        <x-ui.icon name="laptop" size="19"/>
                                    </div>

                                    <div>

                                        <p
                                            class="
                                                font-semibold
                                                text-slate-900
                                            "
                                        >
                                            {{
                                                $unidad->codigo_trazabilidad
                                                ??
                                                'Sin código'
                                            }}
                                        </p>

                                        @if($unidad->serial_fabricante)

                                            <p class="mt-1 text-xs text-slate-500">
                                                Serial:
                                                {{ $unidad->serial_fabricante }}
                                            </p>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- Equipo --}}
                            <td class="px-5 py-4 align-middle">

                                <p class="font-medium text-slate-900">
                                    {{
                                        $unidad->producto?->nombre
                                        ??
                                        $unidad->nombre_equipo
                                        ??
                                        'Equipo sin nombre'
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">

                                    {{
                                        $unidad->producto?->marca?->nombre
                                        ??
                                        'Marca no registrada'
                                    }}

                                    @if(
                                        $unidad->producto?->modelo
                                        ||
                                        $unidad->modelo_equipo
                                    )

                                        ·

                                        {{
                                            $unidad->producto?->modelo
                                            ??
                                            $unidad->modelo_equipo
                                        }}

                                    @endif

                                </p>

                            </td>


                            {{-- Procedencia --}}
                            <td class="px-5 py-4 align-middle">

                                @if($unidad->provieneDeLote())

                                    <div>

                                        <p class="font-medium text-slate-700">
                                            Compra por lote
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            {{
                                                $unidad
                                                    ->detalleLote
                                                    ?->lote
                                                    ?->codigo
                                                ??
                                                'Lote sin código'
                                            }}
                                        </p>

                                    </div>

                                @elseif($unidad->provieneDeAdquisicionDirecta())

                                    <div>

                                        <p class="font-medium text-slate-700">
                                            Compra directa
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            Procedencia alternativa
                                        </p>

                                    </div>

                                @else

                                    <span class="text-slate-400">
                                        Sin procedencia
                                    </span>

                                @endif

                            </td>


                            {{-- Estado --}}
                            <td class="px-5 py-4 align-middle">

                                <x-ui.badge
                                    :color="$colorEstado($unidad->estado)"
                                >
                                    {{ $nombreEstado($unidad->estado) }}
                                </x-ui.badge>

                            </td>


                            {{-- Ubicación --}}
                            <td class="px-5 py-4 align-middle">

                                <div class="flex items-center gap-2 text-slate-700">

                                    <x-ui.icon
                                        name="warehouse"
                                        size="16"
                                        class="text-slate-400"
                                    />

                                    <span>
                                        {{
                                            $unidad->almacenActual?->nombre
                                            ??
                                            'Sin ubicación'
                                        }}
                                    </span>

                                </div>

                            </td>


                            {{-- Acción --}}
                            <td class="px-5 py-4 text-right align-middle">

                                <a
                                    href="{{
                                        route(
                                            'unidades-adquiridas.show',
                                            $unidad
                                        )
                                    }}"
                                    class="
                                        inline-flex
                                        items-center
                                        gap-2
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        px-3.5
                                        py-2
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                        transition
                                        hover:border-oneshop-primary
                                        hover:bg-oneshop-soft
                                        hover:text-oneshop-primary
                                    "
                                >
                                    <x-ui.icon name="eye" size="17"/>

                                    Ver detalle
                                </a>

                            </td>

                        </tr>

                    @endforeach

                    <tr x-show="totalFiltrado === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <x-ui.icon name="package" size="24"/>
                            </div>
                            <h3 class="mt-4 font-semibold text-slate-900">Sin coincidencias</h3>
                            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                                Cambia la búsqueda o el estado seleccionado.
                            </p>
                        </td>
                    </tr>

                </tbody>

            </table>

        </div>




    </x-ui.card>

</div>

</x-layouts.oneshop>