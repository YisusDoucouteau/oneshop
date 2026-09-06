<x-layouts.oneshop
    title="Detalle del equipo | OneShop"
    page-title="Detalle del equipo"
>


<div class="space-y-6">


    {{-- Encabezado principal --}}
    <x-inventario.encabezado
        :equipo="$equipo"
    />



    {{-- Línea de vida --}}
    <x-inventario.timeline
        :eventos="$eventosTrazabilidad"
    />



    {{-- Resumen rápido --}}
    <x-inventario.resumen
        :equipo="$equipo"
    />



    {{-- Identificación + Procedencia --}}
    <div class="grid gap-6 xl:grid-cols-2">


        <x-inventario.identificacion
            :equipo="$equipo"
        />


        <x-inventario.procedencia
            :equipo="$equipo"
        />


    </div>




    {{-- Especificaciones --}}
    <x-inventario.especificaciones
        :equipo="$equipo"
    />




    {{-- Información comercial --}}
    <x-inventario.comercial
        :equipo="$equipo"
    />




    {{-- Garantía --}}
    <x-inventario.garantia
        :equipo="$equipo"
    />




    {{-- Etiqueta --}}
    <x-inventario.etiqueta
        :equipo="$equipo"
    />



</div>


</x-layouts.oneshop>