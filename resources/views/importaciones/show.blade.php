<x-app-layout>

<x-slot name="header">

    <h2 class="font-semibold text-xl text-slate-800">
        Detalle de importación
    </h2>

</x-slot>



<div class="py-6">

<div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">


    {{-- PRUEBA RESUMEN --}}

    <section class="rounded-2xl border bg-white p-6 shadow">

        <h2 class="text-xl font-bold text-slate-900">

            Lote {{ $lote->codigo }}

        </h2>


        <p class="mt-2 text-slate-600">

            Proveedor:

            {{ $lote->proveedor?->nombre ?? 'Sin proveedor' }}

        </p>


        <p class="mt-2 text-slate-600">

            Estado:

            {{ $lote->estado }}

        </p>


    </section>



   @include('importaciones.partials.detalles-lote')


@include('importaciones.partials.equipos-registrados')


@include('importaciones.partials.modal-producto')


@include('importaciones.partials.modal-registrar-equipo')

</div>

</div>


</x-app-layout>