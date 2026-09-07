<x-app-layout>

<x-slot name="header">
    <h2 class="font-semibold text-xl text-gray-800">
        Unidades adquiridas
    </h2>
</x-slot>


<div class="py-6">

<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


@if(session('success'))

<div class="bg-green-100 text-green-800 p-4 rounded mb-4">

{{ session('success') }}

</div>

@endif



<a href="{{ route('unidades-adquiridas.create') }}"
class="bg-blue-600 text-white px-5 py-3 rounded-xl">

+ Registrar equipo comprado

</a>



<div class="mt-6 bg-white shadow rounded-xl p-5">


<table class="w-full">

<thead>

<tr class="border-b">

<th class="text-left p-3">
Código
</th>


<th class="text-left p-3">
Producto
</th>


<th class="text-left p-3">
Modelo
</th>


<th class="text-left p-3">
Estado
</th>


<th class="text-left p-3">
Ubicación
</th>

</tr>

</thead>


<tbody>


@foreach($unidades as $unidad)


<tr class="border-b">


<td class="p-3">

{{ $unidad->codigo_trazabilidad ?? 'Sin código' }}

</td>



<td class="p-3">

{{ $unidad->producto?->marca?->nombre }}

{{ $unidad->producto?->nombre }}

</td>



<td class="p-3">

{{ $unidad->producto?->modelo }}

</td>



<td class="p-3">

<span class="px-3 py-1 rounded bg-gray-100">

{{ $unidad->estado }}

</span>

</td>



<td class="p-3">

{{ $unidad->almacenActual?->nombre }}

</td>



</tr>


@endforeach


</tbody>


</table>



<div class="mt-5">

{{ $unidades->links() }}

</div>


</div>


</div>

</div>


</x-app-layout>