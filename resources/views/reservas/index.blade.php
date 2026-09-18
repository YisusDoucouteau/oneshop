<x-layouts.oneshop
    title="Reservas | OneShop"
    page-title="Reservas"
>

<div class="space-y-6">


<div class="flex items-center justify-between">


<div>

<h1 class="text-2xl font-bold text-slate-900">
Reservas
</h1>

<p class="text-sm text-slate-500">
Control de equipos separados para clientes.
</p>

</div>



<a
href="{{ route('reservas.create') }}"
class="
inline-flex
items-center
gap-2
rounded-xl
bg-oneshop-primary
px-5
py-3
font-semibold
text-white
"
>

<x-ui.icon name="plus"/>

Nueva reserva

</a>


</div>




@if(session('success'))

<div class="rounded-xl bg-green-50 p-4 text-green-700">

{{ session('success') }}

</div>

@endif





<x-ui.card padding="false">


<div class="overflow-x-auto">


<table class="w-full text-sm">


<thead class="border-b bg-slate-50">


<tr>


<th class="px-5 py-4 text-left">
Número
</th>


<th class="px-5 py-4 text-left">
Cliente
</th>


<th class="px-5 py-4 text-left">
Equipos
</th>


<th class="px-5 py-4 text-left">
Estado
</th>


<th class="px-5 py-4 text-left">
Vencimiento
</th>


<th class="px-5 py-4 text-left">
Acción
</th>


</tr>


</thead>





<tbody>


@forelse($reservas as $reserva)



<tr class="border-b hover:bg-slate-50">



<td class="px-5 py-4 font-semibold">

{{ $reserva->numero }}

</td>





<td class="px-5 py-4">


<div class="font-medium">

{{ $reserva->cliente?->nombre_completo }}

</div>


<div class="text-xs text-slate-500">

{{ $reserva->cliente?->telefono }}

</div>


</td>





<td class="px-5 py-4">


<span class="font-semibold">

{{ $reserva->detalles->count() }}

</span>

equipos


</td>





<td class="px-5 py-4">


@if($reserva->estado === 'ACTIVA')


<x-ui.badge color="yellow">

ACTIVA

</x-ui.badge>


@elseif($reserva->estado === 'CONVERTIDA')


<x-ui.badge color="green">

CONVERTIDA

</x-ui.badge>


@else


<x-ui.badge color="gray">

{{ $reserva->estado }}

</x-ui.badge>


@endif



</td>





<td class="px-5 py-4">


{{ $reserva->fecha_expiracion?->format('d/m/Y H:i') }}


</td>





<td class="px-5 py-4">


<a
href="{{ route('reservas.show',$reserva) }}"
class="
rounded-xl
border
px-4
py-2
text-sm
"
>

Ver detalle

</a>


</td>




</tr>



@empty



<tr>

<td
colspan="6"
class="
px-5
py-10
text-center
text-slate-500
"
>

No existen reservas registradas.

</td>

</tr>


@endforelse



</tbody>


</table>


</div>




<div class="p-5">

{{ $reservas->links() }}

</div>



</x-ui.card>



</div>


</x-layouts.oneshop>