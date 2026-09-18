<x-layouts.oneshop
    title="Detalle reserva | OneShop"
    page-title="Detalle de reserva"
>

<div class="space-y-6">


<div class="flex items-center justify-between">


<div>

<h1 class="text-2xl font-bold text-slate-900">
Reserva {{ $reserva->numero }}
</h1>


<p class="text-sm text-slate-500">
Detalle y seguimiento de equipos separados.
</p>


</div>



<div class="flex gap-3">


@if($reserva->estado === 'ACTIVA')


<form
method="POST"
action="{{ route('reservas.cancelar',$reserva) }}"
>

@csrf

<button
class="
rounded-xl
border
border-red-300
px-5
py-3
text-red-600
"
>
Cancelar reserva
</button>

</form>




<form
method="POST"
action="{{ route('reservas.convertirVenta',$reserva) }}"
>

@csrf

<button
class="
rounded-xl
bg-oneshop-primary
px-5
py-3
font-semibold
text-white
"
>
Convertir en venta
</button>

</form>


@endif


</div>


</div>





@if(session('success'))

<div class="rounded-xl bg-green-50 p-4 text-green-700">

{{ session('success') }}

</div>

@endif






<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">



<div class="xl:col-span-2 space-y-6">



<x-ui.card>


<h2 class="font-bold text-lg mb-4">
Cliente
</h2>



<div class="space-y-2">


<div>

<span class="text-slate-500">
Nombre:
</span>

{{ $reserva->cliente?->nombre_completo ?? '-' }}

</div>



<div>

<span class="text-slate-500">
Teléfono:
</span>

{{ $reserva->cliente?->telefono ?? '-' }}

</div>



<div>

<span class="text-slate-500">
Correo:
</span>

{{ $reserva->cliente?->correo ?? '-' }}

</div>


</div>


</x-ui.card>







<x-ui.card>


<h2 class="font-bold text-lg mb-4">
Equipos reservados
</h2>



<div class="space-y-4">



@foreach($reserva->detalles as $detalle)


<div
class="
rounded-xl
border
p-4
"
>


<div class="font-semibold">

{{ $detalle->equipo?->codigo_interno ?? '-' }}

</div>



<div class="text-sm text-slate-500">

{{ $detalle->equipo?->producto?->nombre ?? '' }}

{{ $detalle->equipo?->producto?->modelo ?? '' }}

</div>




<div class="mt-2 text-sm">

Precio acordado:

<strong>

{{ number_format(
    $detalle->precio_acordado,
    2
) }}

Bs

</strong>

</div>


</div>



@endforeach



</div>


</x-ui.card>





</div>






<div class="space-y-6">





<x-ui.card>


<h2 class="font-bold text-lg mb-4">
Estado
</h2>



<div class="space-y-3">



<div>

<x-ui.badge color="yellow">

{{ $reserva->estado }}

</x-ui.badge>

</div>





<div>

<span class="text-slate-500">
Fecha reserva:
</span>


{{
    $reserva->fecha_reserva
        ? $reserva->fecha_reserva->format('d/m/Y H:i')
        : '-'
}}


</div>







<div>

<span class="text-slate-500">
Vencimiento:
</span>


{{
    $reserva->fecha_expiracion
        ? $reserva->fecha_expiracion->format('d/m/Y H:i')
        : '-'
}}


</div>





@if($reserva->fecha_cierre)

<div>

<span class="text-slate-500">
Fecha cierre:
</span>


{{
    $reserva->fecha_cierre->format('d/m/Y H:i')
}}


</div>

@endif




</div>


</x-ui.card>







<x-ui.card>


<h2 class="font-bold text-lg mb-4">
Observación
</h2>


<p class="text-slate-600">

{{ $reserva->observacion ?? '-' }}

</p>


</x-ui.card>






</div>





</div>





</div>


</x-layouts.oneshop>
