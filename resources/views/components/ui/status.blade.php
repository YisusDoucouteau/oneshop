@props([
    'status'
])


@php

$styles = [

    'PENDIENTE_LLEGADA'
    => 'bg-yellow-100 text-yellow-700',


    'RECIBIDA_ORIGEN'
    => 'bg-blue-100 text-blue-700',


    'EN_REVISION'
    => 'bg-purple-100 text-purple-700',


    'EN_PREPARACION'
    => 'bg-orange-100 text-orange-700',


    'LISTA_ENVIO'
    => 'bg-emerald-100 text-emerald-700',


    'ENVIADA'
    => 'bg-indigo-100 text-indigo-700',


    'RECIBIDA_ORURO'
    => 'bg-cyan-100 text-cyan-700',


    'INCORPORADA'
    => 'bg-green-100 text-green-700',


    'ANULADA'
    => 'bg-red-100 text-red-700',

];

@endphp


<span
class="
inline-flex
items-center
rounded-full
px-3
py-1
text-xs
font-semibold
uppercase
tracking-wide
{{ $styles[$status] ?? 'bg-slate-100 text-slate-700' }}
"
>

{{ str_replace('_',' ', $status) }}

</span>