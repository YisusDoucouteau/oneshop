<section class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">


<div class="border-b border-slate-200 px-6 py-5">

<h2 class="text-xl font-bold text-slate-900">
    Lote {{ $lote->codigo }}
</h2>


<p class="mt-1 text-sm text-slate-500">
    Información general de la importación
</p>

</div>



<div class="grid gap-5 p-6 md:grid-cols-4">


<div>
<p class="text-xs uppercase text-slate-500">
Proveedor
</p>

<p class="font-semibold text-slate-900">
{{ $lote->proveedor?->nombre ?? 'Sin proveedor' }}
</p>
</div>



<div>
<p class="text-xs uppercase text-slate-500">
Estado
</p>

<p class="font-semibold text-slate-900">
{{ str_replace('_',' ',$lote->estado) }}
</p>
</div>



<div>
<p class="text-xs uppercase text-slate-500">
Cantidad esperada
</p>

<p class="font-semibold text-slate-900">
{{ $cantidadEsperada }}
</p>
</div>



<div>
<p class="text-xs uppercase text-slate-500">
Cantidad recibida
</p>

<p class="font-semibold text-slate-900">
{{ $cantidadRecibida }}
</p>
</div>


</div>


</section>