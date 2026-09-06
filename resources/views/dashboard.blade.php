<x-layouts.oneshop
    title="Inicio | OneShop"
    page-title="Panel de control"
>


<div class="space-y-8">


    {{-- INDICADORES PRINCIPALES --}}

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-4">


        <x-dashboard.card
            title="Equipos activos"
            value="{{ $totalEquipos }}"
            description="Unidades registradas"
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
            title="En proceso"
            value="{{ $enProceso }}"
            description="Revisión técnica"
            icon="settings"
            color="orange"
        />


        <x-dashboard.card
            title="Ventas realizadas"
            value="{{ $totalVentas }}"
            description="Operaciones comerciales"
            icon="chart"
            color="blue"
        />


    </div>




    {{-- ESTADO INVENTARIO --}}


    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">


        <div class="mb-6">

            <h2 class="text-lg font-semibold text-slate-900">

                Estado del inventario

            </h2>


            <p class="text-sm text-slate-500">

                Distribución actual de equipos registrados

            </p>


        </div>



        <div class="space-y-5">


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


    </div>





    {{-- EQUIPOS RECIENTES --}}


    <div class="grid gap-6 xl:grid-cols-2">


        <x-dashboard.equipment-list

            :equipos="$ultimosEquipos"

        />


    </div>


<div class="grid gap-6 xl:grid-cols-2">


    <x-dashboard.activity

        :movimientos="$ultimosMovimientos"

    />


</div>

</div>


</x-layouts.oneshop>