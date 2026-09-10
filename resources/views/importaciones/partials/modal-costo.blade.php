<div
    id="modalCosto"
    class="fixed inset-0 z-50 hidden bg-black/50"
>

<div class="flex min-h-screen items-center justify-center p-4">


<div class="w-full max-w-xl rounded-2xl bg-white shadow-xl">


<div class="flex justify-between border-b px-6 py-4">

<h2 class="font-semibold text-lg">
Registrar costo de importación
</h2>


<button
type="button"
onclick="cerrarModalCosto()"
class="text-xl text-slate-500"
>
✕
</button>

</div>




<form
method="POST"
action="{{ route('importaciones.costos.store',$lote) }}"
class="space-y-4 p-6"
>


@csrf



<div>

<label class="text-sm font-medium">
Tipo de costo
</label>


<select
name="tipo_costo_id"
class="mt-1 w-full rounded-xl border p-3"
required
>


<option value="">
Seleccione
</option>


@php
$tiposCosto = \App\Models\TipoCosto::where('activo', true)
    ->whereIn('ambito', ['LOTE','AMBOS'])
    ->get();
@endphp


@foreach($tiposCosto as $tipo)

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
Tipo de cambio aplicado
</label>


<input

type="number"

step="0.000001"

name="tipo_cambio"

placeholder="Ej: 6.96"

class="w-full rounded-xl border p-3"

>


<p class="text-xs text-slate-500">
Solo necesario para USD o USDT.
</p>


</div>





<div>


<label class="text-sm">
Fecha del costo
</label>


<input

type="date"

name="fecha_costo"

value="{{ date('Y-m-d') }}"

class="w-full rounded-xl border p-3"

required

>


</div>




<input

name="referencia"

placeholder="Referencia"

class="w-full rounded-xl border p-3"

/>




<textarea

name="observacion"

placeholder="Observación"

class="w-full rounded-xl border p-3"

></textarea>





<div class="flex justify-end gap-3">


<button
type="button"
onclick="cerrarModalCosto()"
class="rounded-xl border px-5 py-2"
>

Cancelar

</button>



<button
class="rounded-xl bg-slate-950 px-5 py-2 text-white"
>

Guardar costo

</button>


</div>



</form>


</div>

</div>

</div>




<script>

function abrirModalCosto(){

document
.getElementById('modalCosto')
.classList
.remove('hidden');

}


function cerrarModalCosto(){

document
.getElementById('modalCosto')
.classList
.add('hidden');

}

</script>