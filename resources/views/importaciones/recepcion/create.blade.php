@extends('layouts.app')

@section('content')

<div class="py-6">

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8">


@if($errors->any())

<div class="mb-5 rounded-xl bg-red-100 p-4 text-red-700">

<ul class="list-disc ml-5">

@foreach($errors->all() as $error)

<li>
{{ $error }}
</li>

@endforeach

</ul>

</div>

@endif




<div class="bg-white shadow rounded-2xl p-6">


<h1 class="text-2xl font-bold text-gray-800">
Registrar llegada a Cochabamba
</h1>


<p class="mt-2 text-sm text-gray-500">

Registro inicial de unidades adquiridas.
Todavía no ingresan al inventario.

</p>





{{-- Producto --}}

<div class="mt-6 bg-gray-50 rounded-xl p-5">


<h2 class="font-semibold text-gray-700">
Producto del lote
</h2>


<div class="grid md:grid-cols-2 gap-4 mt-4">


<div>

<p class="text-xs text-gray-500">
Marca
</p>

<p class="font-semibold">
{{ $detalle->producto->marca?->nombre }}
</p>

</div>



<div>

<p class="text-xs text-gray-500">
Producto
</p>

<p class="font-semibold">
{{ $detalle->producto->nombre }}
</p>

</div>




<div>

<p class="text-xs text-gray-500">
Modelo
</p>

<p class="font-semibold">
{{ $detalle->producto->modelo }}
</p>

</div>




<div>

<p class="text-xs text-gray-500">
Pendientes por recibir
</p>

<p class="font-semibold">
{{ $pendientes }}
</p>

</div>


</div>


</div>






<form method="POST"
action="{{ route('importaciones.recepcion.store',[
'lote'=>$lote->id,
'detalle'=>$detalle->id
]) }}"
class="mt-6 space-y-6"
>


@csrf




{{-- Cantidad --}}

<div>

<label class="block font-medium text-gray-700">

Cantidad llegada

</label>


<input
type="number"
name="cantidad"
min="1"
max="{{ $pendientes }}"
value="{{ old('cantidad',1) }}"
class="mt-1 w-full rounded-xl border-gray-300"
>


<p class="text-xs text-gray-500 mt-1">

Ejemplo: si llegaron 5 equipos iguales, colocar 5.

</p>


</div>






{{-- Serial --}}

<div>


<label class="block font-medium text-gray-700">

Serial fabricante (opcional)

</label>


<input

type="text"

name="serial_fabricante"

value="{{ old('serial_fabricante') }}"

placeholder="Ej. ABC123456"

class="mt-1 w-full rounded-xl border-gray-300"

>


<p class="text-xs text-gray-500 mt-1">

Si son varias unidades, la identificación detallada se realizará posteriormente en revisión.

</p>


</div>







{{-- Observación --}}

<div>


<label class="block font-medium text-gray-700">

Observación de llegada

</label>



<textarea

name="observacion"

rows="4"

placeholder="Ej. cajas con desgaste, faltan accesorios, recibido correctamente..."

class="mt-1 w-full rounded-xl border-gray-300"

>{{ old('observacion') }}</textarea>



</div>






<div class="flex justify-end">


<button

type="submit"

class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl"

>

Registrar llegada

</button>


</div>





</form>



</div>


</div>


</div>


@endsection