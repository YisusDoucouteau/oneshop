<x-app-layout>


<x-slot name="header">

    <h2 class="font-semibold text-xl text-gray-800">
        Registrar equipo comprado
    </h2>

</x-slot>



<div class="py-6">

<div class="max-w-5xl mx-auto sm:px-6 lg:px-8">


<div class="bg-white shadow rounded-xl p-6">


<form method="POST" action="{{ route('unidades-adquiridas.store') }}">

@csrf



<h3 class="text-lg font-bold mb-5">
Datos principales
</h3>



<label class="block mb-2">
Lote
</label>

<select 
name="detalle_lote_id"
class="border rounded w-full p-2 mb-5">


@foreach($lotes as $lote)

@foreach($lote->detalles as $detalle)

<option value="{{ $detalle->id }}">

{{ $lote->codigo }}

</option>

@endforeach

@endforeach


</select>




<label class="block mb-2">
Nombre del equipo
</label>

<input
name="nombre_equipo"
class="border rounded w-full p-2 mb-5"
placeholder="Ejemplo: Dell Latitude"
required>



<label class="block mb-2">
Modelo
</label>

<input
name="modelo_equipo"
class="border rounded w-full p-2 mb-5"
placeholder="Ejemplo: 5420">



<div class="grid grid-cols-2 gap-4">


<div>

<label>
Precio compra
</label>

<input
type="number"
step="0.01"
name="precio_compra"
class="border rounded w-full p-2">

</div>


<div>

<label>
Moneda
</label>


<select
name="moneda_id"
class="border rounded w-full p-2">


@foreach($monedas as $moneda)

<option value="{{ $moneda->id }}">

{{ $moneda->codigo }}

</option>

@endforeach


</select>


</div>


</div>



<hr class="my-6">



<h3 class="font-bold mb-4">
Características conocidas
</h3>



<input
name="procesador"
placeholder="Procesador"
class="border rounded w-full p-2 mb-3">



<input
name="generacion_procesador"
placeholder="Generación"
class="border rounded w-full p-2 mb-3">



<div class="grid grid-cols-2 gap-4">


<input
type="number"
name="ram_gb"
placeholder="RAM GB"
class="border rounded p-2">


<input
type="number"
name="almacenamiento_gb"
placeholder="Almacenamiento GB"
class="border rounded p-2">


</div>



<input
name="tarjeta_grafica"
placeholder="Tarjeta gráfica"
class="border rounded w-full p-2 mt-3">



<input
name="sistema_operativo"
placeholder="Sistema operativo"
class="border rounded w-full p-2 mt-3">



<textarea
name="observacion_revision"
placeholder="Observación"
class="border rounded w-full p-2 mt-3"></textarea>




<button
class="mt-6 bg-green-600 text-white px-6 py-3 rounded-xl">

Guardar equipo

</button>



</form>


</div>


</div>

</div>


</x-app-layout>