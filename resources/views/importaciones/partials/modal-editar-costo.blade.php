<div
id="modalEditarCosto"
class="fixed inset-0 z-50 hidden bg-black/60"
>

<div class="flex min-h-screen items-center justify-center p-4">

<div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">


<div class="flex justify-between bg-slate-950 px-6 py-4">

<h2 class="font-semibold text-white">
Editar costo de importación
</h2>


<button
type="button"
onclick="cerrarEditarCosto()"
class="text-xl text-white">
×
</button>


</div>



<form

id="formEditarCosto"

method="POST"

class="space-y-4 p-6"

>


@csrf

@method('PUT')



<div>

<label class="text-sm font-medium">
Tipo de costo
</label>


@php
$tiposCostoEditar = \App\Models\TipoCosto::where('activo', true)
    ->whereIn('ambito', ['LOTE','AMBOS'])
    ->get();
@endphp


<select
id="editar_tipo"
name="tipo_costo_id"
class="w-full rounded-xl border p-3"
required
>


@foreach($tiposCostoEditar as $tipo)

<option value="{{ $tipo->id }}">

{{ $tipo->nombre }}

</option>

@endforeach


</select>


</div>




<div class="grid grid-cols-2 gap-4">


<div>

<label class="text-sm">
Moneda
</label>


<select

id="editar_moneda"

name="moneda_id"

class="w-full rounded-xl border p-3"

required

>


<option value="1">
BOB
</option>


<option value="2">
USD
</option>


<option value="3">
USDT
</option>


</select>


</div>




<div>

<label class="text-sm">
Monto
</label>


<input

id="editar_monto"

type="number"

step="0.01"

name="monto_origen"

class="w-full rounded-xl border p-3"

required

>


</div>


</div>




<div>


<label class="text-sm">
Tipo de cambio
</label>


<input

id="editar_tipo_cambio"

type="number"

step="0.000001"

name="tipo_cambio"

class="w-full rounded-xl border p-3"

>


</div>




<div>


<label class="text-sm">
Fecha costo
</label>


<input

id="editar_fecha"

type="date"

name="fecha_costo"

class="w-full rounded-xl border p-3"

required

>


</div>




<input

id="editar_referencia"

name="referencia"

placeholder="Referencia"

class="w-full rounded-xl border p-3"

/>




<textarea

id="editar_observacion"

name="observacion"

placeholder="Observación"

class="w-full rounded-xl border p-3"

></textarea>




<div class="flex justify-end gap-3">


<button

type="button"

onclick="cerrarEditarCosto()"

class="rounded-xl border px-5 py-2"

>

Cancelar

</button>



<button

class="rounded-xl bg-slate-950 px-5 py-2 text-white"

>

Guardar cambios

</button>


</div>



</form>



</div>

</div>

</div>



<script>


function editarCosto(
id,
tipo,
moneda,
monto,
fecha,
referencia,
observacion,
tipoCambio
){

document
.getElementById('editar_tipo_cambio')
.value = tipoCambio ?? '';

document
.getElementById('modalEditarCosto')
.classList
.remove('hidden');



document
.getElementById('formEditarCosto')
.action =
'/importaciones/costos/' + id;



document
.getElementById('editar_tipo')
.value = tipo;



document
.getElementById('editar_moneda')
.value = moneda;



document
.getElementById('editar_monto')
.value = monto;



document
.getElementById('editar_fecha')
.value = fecha;



document
.getElementById('editar_referencia')
.value = referencia ?? '';



document
.getElementById('editar_observacion')
.value = observacion ?? '';



}



function cerrarEditarCosto(){


document
.getElementById('modalEditarCosto')
.classList
.add('hidden');


}


</script>