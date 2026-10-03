<x-layouts.oneshop title="Inventario | OneShop" page-title="Inventario">

@php
    $registrosFiltro = $equipos
        ->map(function ($equipo) {
            return [
                'id' => (int) $equipo->id,
                'estado' => (string) ($equipo->estado_actual_id ?? ''),
                'almacen' => (string) ($equipo->almacen_actual_id ?? ''),
                'texto' => implode(' ', array_filter([
                    $equipo->codigo_interno,
                    $equipo->serial_fabricante,
                    $equipo->producto?->nombre,
                    $equipo->producto?->modelo,
                    $equipo->producto?->marca?->nombre,
                    $equipo->almacenActual?->nombre,
                    $equipo->estadoActual?->nombre,
                ])),
            ];
        })
        ->values();
@endphp

<div
    x-data="{
        buscar: @js($busqueda),
        estado: @js($estadoId > 0 ? (string) $estadoId : ''),
        almacen: @js($almacenId > 0 ? (string) $almacenId : ''),
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
                && (this.almacen === '' || String(registro.almacen) === String(this.almacen))
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
            this.almacen = '';
        }
    }"
>

    {{-- Encabezado --}}

    <div class="
        mb-8
        flex
        flex-col
        gap-4
        sm:flex-row
        sm:items-center
        sm:justify-between
        ">

        <div>

            <h1 class="
                text-2xl
                font-bold
                tracking-tight
                text-slate-950
                ">

                Inventario de equipos

            </h1>


            <p class="mt-1 text-sm text-slate-500">

                Consulta y seguimiento de los equipos físicos registrados en OneShop.

            </p>


        </div>




   <div class="flex flex-wrap gap-2">

    <a
        href="{{ route('inventario.componentes.index') }}"
        class="
            inline-flex
            items-center
            justify-center
            gap-2
            rounded-xl
            border
            border-slate-300
            bg-white
            px-4
            py-2.5
            text-sm
            font-semibold
            text-slate-700
            transition
            hover:bg-slate-50
        "
    >
        <x-ui.icon
            name="settings"
            size="18"
        />

        Componentes
    </a>

    @if(auth()->user()->tienePermiso('inventario.registrar'))

        <a
            href="{{ route('inventario.create') }}"
            class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                border
                border-oneshop-primary
                bg-oneshop-light
                px-4
                py-2.5
                text-sm
                font-semibold
                text-oneshop-dark
                transition
                hover:bg-blue-100
            "
        >
            + Registro manual
        </a>

    @endif

</div>

    </div>







    {{-- Métricas --}}


    <div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">



        {{-- Total equipos --}}


        <div class="
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
            transition
            hover:-translate-y-1
            hover:shadow-md
            ">


            <div class="flex items-center gap-3">


                <div class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-xl
                    bg-blue-50
                    text-blue-600
                    ">

                    <x-ui.icon name="package" size="24" />

                </div>



                <p class="text-sm font-medium text-slate-500">

                    Equipos registrados

                </p>


            </div>



            <p class="
                mt-5
                text-3xl
                font-black
                tracking-tight
                text-slate-950
                ">

                {{ number_format($totalEquipos) }}

            </p>


            <p class="mt-1 text-xs text-slate-400">

                Total de activos en inventario

            </p>


        </div>







        {{-- Estados dinámicos --}}


        @foreach($resumenEstados->take(3) as $estado)


        <div class="
                rounded-2xl
                border
                border-slate-200
                bg-white
                p-5
                shadow-sm
                transition
                hover:-translate-y-1
                hover:shadow-md
                ">


            <div class="flex items-center gap-3">


                <div class="
                        flex
                        h-11
                        w-11
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-100
                        text-slate-600
                        ">

                    <x-ui.icon name="settings" size="24" />

                </div>


                <p class="text-sm font-medium text-slate-500">

                    {{ $estado->nombre }}

                </p>


            </div>




            <p class="
                    mt-5
                    text-3xl
                    font-black
                    tracking-tight
                    text-slate-950
                    ">

                {{ number_format($estado->cantidad) }}

            </p>


            <p class="mt-1 text-xs text-slate-400">

                Equipos actualmente

            </p>


        </div>



        @endforeach



    </div>







    {{-- Pendientes de incorporación --}}

    @if(
        $unidadesPendientesIncorporacion->isNotEmpty()
        && auth()->user()?->tienePermiso('importacion.ver')
    )

        <div class="mb-8 overflow-hidden rounded-2xl border border-blue-200 bg-white shadow-sm">

            <div class="flex flex-col gap-2 border-b border-blue-100 bg-blue-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-oneshop-primary">
                        Recepción Oruro
                    </p>
                    <h2 class="mt-1 font-semibold text-slate-900">
                        Unidades pendientes de incorporación
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        Ya llegaron físicamente y su recepción logística fue cerrada.
                    </p>
                </div>

                <a
                    href="{{ route('unidades-adquiridas.index', ['estado' => \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORURO]) }}"
                    class="text-sm font-semibold text-oneshop-primary hover:text-oneshop-dark"
                >
                    Ver todas →
                </a>
            </div>

            <div class="divide-y divide-slate-100">
                @foreach($unidadesPendientesIncorporacion->take(5) as $unidad)
                    <div class="flex flex-col gap-3 px-5 py-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <a
                                href="{{ route('unidades-adquiridas.show', $unidad) }}"
                                class="font-semibold text-oneshop-primary hover:text-oneshop-dark"
                            >
                                {{ $unidad->codigo_trazabilidad ?? 'Sin código' }}
                            </a>
                            <p class="mt-1 text-sm font-medium text-slate-900">
                                {{ $unidad->producto?->nombre ?? $unidad->nombre_equipo ?? 'Equipo' }}
                                @if($unidad->producto?->modelo ?? $unidad->modelo_equipo)
                                    · {{ $unidad->producto?->modelo ?? $unidad->modelo_equipo }}
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $unidad->envioImportacionUnidad?->envioImportacion?->codigo ?? 'Ingreso sin envío asociado' }}
                            </p>
                        </div>

                        @if(auth()->user()?->tienePermiso('inventario.registrar'))
                            <a
                                href="{{ route('unidades-adquiridas.show', $unidad) }}"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-4 py-2.5 text-sm font-semibold text-oneshop-dark transition hover:bg-blue-100"
                            >
                                <x-ui.icon name="package" size="17" />
                                Revisar e incorporar
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

        </div>

    @endif


    {{-- Panel --}}


    <div class="
        overflow-hidden
        rounded-2xl
        border
        border-slate-200
        bg-white
        shadow-sm
        ">



        {{-- Filtros --}}


        <div class="
            border-b
            border-slate-200
            p-5
            ">


            <div class="grid gap-3 lg:grid-cols-12">



                <div class="lg:col-span-6">


                    <label for="buscar" class="sr-only">

                        Buscar

                    </label>


                    <input id="buscar" type="search" x-model.debounce.150ms="buscar"
                        placeholder="Código, serial, producto o modelo..." class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                        focus:border-slate-900
                        focus:ring-slate-900
                        ">


                </div>
                <div class="lg:col-span-2">

                    <select x-model="estado" class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                        focus:border-slate-900
                        focus:ring-slate-900
                        ">

                        <option value="">
                            Todos los estados
                        </option>


                        @foreach($estados as $estado)

                        <option value="{{ $estado->id }}"

                            >

                            {{ $estado->nombre }}

                        </option>


                        @endforeach


                    </select>


                </div>






                <div class="lg:col-span-2">


                    <select x-model="almacen" class="
                        w-full
                        rounded-xl
                        border-slate-300
                        text-sm
                        focus:border-slate-900
                        focus:ring-slate-900
                        ">


                        <option value="">

                            Todos los almacenes

                        </option>




                        @foreach($almacenes as $almacen)



                        <option value="{{ $almacen->id }}"

                            >

                            {{ $almacen->nombre }}

                        </option>



                        @endforeach



                    </select>


                </div>






                <div class="flex items-end lg:col-span-2">
                    <button
                        type="button"
                        @click="limpiar()"
                        :disabled="buscar === '' && estado === '' && almacen === ''"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <x-ui.icon name="x" size="17" />
                        Limpiar
                    </button>
                </div>


            </div>


        </div>








        {{-- Tabla --}}

        <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div>
                <h2 class="font-semibold text-slate-900">Equipos formalizados</h2>
                <p class="text-xs text-slate-500">
                    <span x-text="totalFiltrado"></span> de {{ $equipos->count() }} equipos visibles
                </p>
            </div>
            <span
                x-show="buscar !== '' || estado !== '' || almacen !== ''"
                class="text-xs font-medium text-oneshop-primary"
            >
                Filtro local activo
            </span>
        </div>

        <div class="overflow-x-auto">


            <table class="min-w-full divide-y divide-slate-200">


                <thead class="bg-slate-50">


                    <tr>


                        <th class="
                            px-5
                            py-3
                            text-left
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-slate-500
                            ">

                            Código

                        </th>



                        <th class="
                            px-5
                            py-3
                            text-left
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-slate-500
                            ">

                            Equipo

                        </th>



                        <th class="
                            px-5
                            py-3
                            text-left
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-slate-500
                            ">

                            Almacén

                        </th>



                        <th class="
                            px-5
                            py-3
                            text-left
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-slate-500
                            ">

                            Estado

                        </th>



                        <th class="
                            px-5
                            py-3
                            text-right
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wider
                            text-slate-500
                            ">

                            Precio

                        </th>



                        <th class="px-5 py-3">

                        </th>


                    </tr>


                </thead>





                <tbody class="divide-y divide-slate-100 bg-white">



                    @foreach($equipos as $equipo)



                    <tr
                        x-show="visiblePorId({{ $equipo->id }})"
                        class="transition hover:bg-slate-50"
                    >



                        <td class="whitespace-nowrap px-5 py-4">


                            <span class="
                                font-semibold
                                text-slate-950
                                ">

                                {{ $equipo->codigo_interno }}

                            </span>



                            @if($equipo->serial_fabricante)


                            <p class="mt-1 text-xs text-slate-400">


                                {{ $equipo->serial_fabricante }}


                            </p>


                            @endif


                        </td>






                        <td class="px-5 py-4">


                            <p class="font-medium text-slate-900">


                                {{ $equipo->producto?->nombre ?? 'Sin producto' }}


                            </p>


                            <p class="mt-1 text-sm text-slate-500">


                                {{ $equipo->producto?->marca?->nombre }}


                                @if($equipo->producto?->modelo)

                                · {{ $equipo->producto->modelo }}

                                @endif


                            </p>



                        </td>
                        <td class="
                            whitespace-nowrap
                            px-5
                            py-4
                            text-sm
                            text-slate-600
                            ">

                            {{ $equipo->almacenActual?->nombre ?? '—' }}


                        </td>







                        <td
    class="
    whitespace-nowrap
    px-5
    py-4
    "
>


@php

$estadoNombre =
$equipo->estadoActual?->nombre ?? 'Sin estado';


$estiloEstado = match($estadoNombre) {

    'Disponible' =>
        'bg-green-100 text-green-700',


    'Reservado' =>
        'bg-purple-100 text-purple-700',


    'Vendido' =>
        'bg-emerald-100 text-emerald-700',


    'En reparación' =>
        'bg-orange-100 text-orange-700',


    'En garantía' =>
        'bg-red-100 text-red-700',


    'En diagnóstico' =>
        'bg-blue-100 text-blue-700',


    'Pendiente de revisión' =>
        'bg-yellow-100 text-yellow-700',


    default =>
        'bg-slate-100 text-slate-700',

};


@endphp




<span

    class="
    inline-flex
    items-center
    rounded-full
    px-3
    py-1
    text-xs
    font-semibold
    {{ $estiloEstado }}
    "

>


{{ $estadoNombre }}


</span>


</td>







                        <td
    class="
    whitespace-nowrap
    px-5
    py-4
    text-right
    "
>


@if($equipo->precioVigente)


    <div>


        @if(auth()->user()->tienePermiso('precios.ver'))


            <p
                class="
                font-bold
                text-slate-900
                "
            >

                Bs {{ number_format(
                    (float)$equipo->precioVigente->precio_publico,
                    2
                ) }}

            </p>


            <p
                class="
                mt-1
                text-xs
                text-slate-400
                "
            >

                Precio público

            </p>


        @else


            <span class="text-sm text-slate-400">

                No autorizado

            </span>


        @endif



    </div>



@else


    <span class="text-sm text-slate-400">

        Sin precio

    </span>



@endif



</td>







                        <td
    class="
    whitespace-nowrap
    px-5
    py-4
    text-right
    "
>


<div class="flex justify-end gap-2">


    <a

        href="{{ route('inventario.show', $equipo->codigo_interno) }}"

        class="
        inline-flex
        items-center
        rounded-xl
        border
        border-oneshop-primary
        bg-oneshop-light
        px-4
        py-2
        text-sm
        font-semibold
        text-oneshop-dark
        transition
        hover:bg-blue-100
        "

    >

        Ver ficha

    </a>







</div>


</td>



                    </tr>





                    @endforeach

                    <tr x-show="totalFiltrado === 0">
                        <td colspan="6" class="px-5 py-16 text-center">
                            <div class="mx-auto max-w-sm">
                                <p class="text-lg font-semibold text-slate-900">Sin coincidencias</p>
                                <p class="mt-2 text-sm text-slate-500">
                                    Cambia la búsqueda, el estado o el almacén seleccionado.
                                </p>
                            </div>
                        </td>
                    </tr>



                </tbody>



            </table>



        </div>

    </div>

</div>

</x-layouts.oneshop>
