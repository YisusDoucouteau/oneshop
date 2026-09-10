<x-app-layout>


<x-slot name="header">

Editar equipo recibido

</x-slot>



<div class="mx-auto max-w-5xl px-6 py-8">


<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">


{{-- HEADER --}}

<div class="flex items-center gap-3 bg-slate-950 px-6 py-5 text-white">

<i data-lucide="monitor-edit"
class="h-6 w-6">
</i>


<div>

<h1 class="font-semibold">
Editar equipo recibido
</h1>

<p class="text-sm text-slate-300">
{{ $unidad->codigo_trazabilidad }}
</p>

</div>


</div>





<form
method="POST"
action="{{ route('importaciones.unidades.actualizar',$unidad) }}"
class="p-6"
>


@csrf
@method('PATCH')





{{-- INFORMACION EQUIPO --}}

<section>


<h2 class="mb-5 flex items-center gap-2 text-lg font-semibold">

<i data-lucide="laptop"
class="h-5 w-5 text-slate-600">
</i>

Información del equipo

</h2>




<div class="grid grid-cols-2 gap-5">


<div>

<label class="text-sm font-medium">
Procesador
</label>


<input
name="procesador"
value="{{ old('procesador',$unidad->procesador) }}"
class="mt-2 w-full rounded-xl border p-3"
>


</div>




<div>

<label class="text-sm font-medium">
Generación
</label>


<input
name="generacion_procesador"
value="{{ old('generacion_procesador',$unidad->generacion_procesador) }}"
class="mt-2 w-full rounded-xl border p-3"
>


</div>




<div>

<label class="text-sm font-medium">
RAM GB
</label>


<input
type="number"
name="ram_gb"
value="{{ old('ram_gb',$unidad->ram_gb) }}"
class="mt-2 w-full rounded-xl border p-3"
>


</div>




<div>

<label class="text-sm font-medium">
Disco GB
</label>


<input
type="number"
name="almacenamiento_gb"
value="{{ old('almacenamiento_gb',$unidad->almacenamiento_gb) }}"
class="mt-2 w-full rounded-xl border p-3"
>


</div>



<div>


<label class="text-sm font-medium">
Tipo disco
</label>


<select
name="tipo_almacenamiento"
class="mt-2 w-full rounded-xl border p-3"
>


<option value="">
Seleccione
</option>


<option value="SSD"
@if($unidad->tipo_almacenamiento=="SSD") selected @endif
>
SSD
</option>


<option value="NVME"
@if($unidad->tipo_almacenamiento=="NVME") selected @endif
>
NVMe
</option>


<option value="HDD"
@if($unidad->tipo_almacenamiento=="HDD") selected @endif
>
HDD
</option>


</select>


</div>




<div>

<label class="text-sm font-medium">
GPU
</label>


<input
name="tarjeta_grafica"
value="{{ old('tarjeta_grafica',$unidad->tarjeta_grafica) }}"
class="mt-2 w-full rounded-xl border p-3"
>


</div>




<div>

<label class="text-sm font-medium">
Serial fabricante
</label>


<input
name="serial_fabricante"
value="{{ old('serial_fabricante',$unidad->serial_fabricante) }}"
class="mt-2 w-full rounded-xl border p-3"
>


</div>




<div>


<label class="text-sm font-medium">
Cargador
</label>


<select
name="tiene_cargador"
class="mt-2 w-full rounded-xl border p-3"
>


<option value="1"
@if($unidad->tiene_cargador)
selected
@endif
>
Sí
</option>


<option value="0"
@if(!$unidad->tiene_cargador)
selected
@endif
>
No
</option>


</select>


</div>


</div>


</section>








<hr class="my-8">





{{-- DETALLES --}}


<section>


<h2 class="mb-5 flex items-center gap-2 text-lg font-semibold">

<i data-lucide="settings"
class="h-5 w-5 text-slate-600">
</i>


Detalles adicionales

</h2>



<div class="grid grid-cols-2 gap-5">



<input
name="sistema_operativo"
value="{{ old('sistema_operativo',$unidad->sistema_operativo) }}"
placeholder="Sistema operativo"
class="rounded-xl border p-3"
>



<input
name="resolucion"
value="{{ old('resolucion',$unidad->resolucion) }}"
placeholder="Resolución"
class="rounded-xl border p-3"
>




<input
name="pantalla_pulgadas"
value="{{ old('pantalla_pulgadas',$unidad->pantalla_pulgadas) }}"
placeholder="Pulgadas"
class="rounded-xl border p-3"
>



<input
name="servicio_requerido"
value="{{ old('servicio_requerido',$unidad->servicio_requerido) }}"
placeholder="Servicio requerido"
class="rounded-xl border p-3"
>



</div>




<textarea
name="observacion_revision"
class="mt-5 w-full rounded-xl border p-3"
placeholder="Observación"
>{{ old('observacion_revision',$unidad->observacion_revision) }}</textarea>



</section>








<div class="mt-8 flex justify-end gap-3 border-t pt-5">


<a
href="{{ route('importaciones.show',$unidad->detalleLote->lote) }}"
class="rounded-xl border px-5 py-2"
>
Cancelar
</a>



<button
class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2 font-semibold text-white"
>


<i data-lucide="save"
class="h-4 w-4">
</i>


Guardar cambios

</button>


</div>




</form>



</div>



</div>





@push('scripts')

<script>

lucide.createIcons();

</script>

@endpush



</x-app-layout>