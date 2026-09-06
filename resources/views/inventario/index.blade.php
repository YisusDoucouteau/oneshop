<x-layouts.oneshop title="Inventario | OneShop" page-title="Inventario">


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




        @if(auth()->user()->tienePermiso('inventario.registrar'))

        <a href="{{ route('inventario.create') }}" class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                bg-slate-950
                px-4
                py-2.5
                text-sm
                font-semibold
                text-white
                transition
                hover:bg-slate-800
                ">

            + Registrar equipo

        </a>

        @endif


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


        <form method="GET" action="{{ route('inventario.index') }}" class="
            border-b
            border-slate-200
            p-5
            ">


            <div class="grid gap-3 lg:grid-cols-12">



                <div class="lg:col-span-6">


                    <label for="buscar" class="sr-only">

                        Buscar

                    </label>


                    <input id="buscar" name="buscar" type="search" value="{{ $busqueda }}"
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

                    <select name="estado" class="
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

                        <option value="{{ $estado->id }}" @selected($estadoId===$estado->id)

                            >

                            {{ $estado->nombre }}

                        </option>


                        @endforeach


                    </select>


                </div>






                <div class="lg:col-span-2">


                    <select name="almacen" class="
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



                        <option value="{{ $almacen->id }}" @selected($almacenId===$almacen->id)

                            >

                            {{ $almacen->nombre }}

                        </option>



                        @endforeach



                    </select>


                </div>






                <div class="flex gap-2 lg:col-span-2">


                    <button type="submit" class="
                        flex-1
                        rounded-xl
                        bg-slate-900
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-slate-800
                        ">

                        Filtrar

                    </button>



                    <a href="{{ route('inventario.index') }}" class="
                        rounded-xl
                        border
                        border-slate-300
                        px-4
                        py-2
                        text-sm
                        font-semibold
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        ">

                        ×

                    </a>



                </div>


            </div>


        </form>








        {{-- Tabla --}}


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



                    @forelse($equipos as $equipo)



                    <tr class="
                        transition
                        hover:bg-slate-50
                        ">



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
        bg-slate-900
        px-4
        py-2
        text-sm
        font-semibold
        text-white
        transition
        hover:bg-slate-800
        "

    >

        Ver ficha

    </a>




    <button

        type="button"

        class="
        inline-flex
        items-center
        rounded-xl
        border
        border-slate-300
        px-3
        py-2
        text-sm
        font-semibold
        text-slate-600
        transition
        hover:bg-slate-50
        "

        title="Escanear QR próximamente"

    >

        QR

    </button>


</div>


</td>



                    </tr>





                    @empty



                    <tr>


                        <td colspan="6" class="
                            px-5
                            py-16
                            text-center
                            ">


                            <div class="mx-auto max-w-sm">


                                <p class="
                                    text-lg
                                    font-semibold
                                    text-slate-900
                                    ">

                                    No se encontraron equipos

                                </p>



                                <p class="
                                    mt-2
                                    text-sm
                                    text-slate-500
                                    ">

                                    Prueba cambiando los filtros o el término de búsqueda.

                                </p>


                            </div>



                        </td>



                    </tr>



                    @endforelse



                </tbody>



            </table>



        </div>

        {{-- Paginación --}}



        @if($equipos->hasPages())


        <div class="
                border-t
                border-slate-200
                px-5
                py-4
                ">

            {{ $equipos->links() }}


        </div>


        @endif




    </div>



</x-layouts.oneshop>