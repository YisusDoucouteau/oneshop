<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    @php

    $lote->load([

    'detalles.unidadesAdquiridas.producto.marca',

    'detalles.unidadesAdquiridas.almacenActual',

    'detalles.unidadesAdquiridas.moneda',

    ]);


    $unidades =

    $lote->detalles

    ->pluck('unidadesAdquiridas')

    ->flatten()

    ->sortByDesc('created_at');


    @endphp




    {{-- HEADER --}}

    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">


        <div>

            <h2 class="font-semibold text-slate-950">

                Equipos registrados físicamente

            </h2>


            <p class="mt-1 text-sm text-slate-500">

                Equipos identificados durante la recepción del lote.

                Todavía no forman parte del inventario definitivo.

            </p>


        </div>




        <button type="button" onclick="abrirModalEquipo()"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">


            <i data-lucide="monitor-plus" class="h-4 w-4">

            </i>


            Registrar equipo recibido


        </button>


    </div>







    <div class="overflow-x-auto">


        <table class="min-w-full divide-y divide-slate-200">


            <thead class="bg-slate-50">


                <tr>


                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">

                        Código trazabilidad

                    </th>


                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">

                        Equipo

                    </th>


                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">

                        Características

                    </th>


                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">

                        Compra

                    </th>


                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">

                        Estado

                    </th>


                    <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">

                        Acciones

                    </th>


                </tr>


            </thead>





            <tbody class="divide-y divide-slate-100">



                @forelse($unidades as $unidad)



                <tr class="hover:bg-slate-50 transition">



                    {{-- CODIGO --}}

                    <td class="px-6 py-5">


                        <p class="font-semibold text-slate-900">

                            {{ $unidad->codigo_trazabilidad ?? 'Pendiente' }}

                        </p>


                        <p class="text-xs text-slate-500">

                            {{ $unidad->created_at?->format('d/m/Y H:i') }}

                        </p>


                    </td>





                    {{-- EQUIPO --}}

                    <td class="px-6 py-5">


                        <p class="font-semibold text-slate-900">


                            {{ $unidad->producto?->marca?->nombre }}

                            {{ $unidad->producto?->nombre }}


                        </p>



                        <p class="text-xs text-slate-500">

                            Modelo:

                            {{ $unidad->producto?->modelo ?? 'Sin modelo' }}

                        </p>


                    </td>





                    {{-- CARACTERISTICAS --}}

                    <td class="px-6 py-5">


                        <div class="space-y-1 text-sm text-slate-600">



                            @if($unidad->procesador)

                            <p>

                                CPU:

                                {{ $unidad->procesador }}

                                {{ $unidad->generacion_procesador }}

                            </p>

                            @endif



                            @if($unidad->ram_gb !== null)

                            <p>

                                RAM:

                                {{ $unidad->ram_gb }} GB

                                @if((int) $unidad->ram_gb === 0)
                                    <span class="font-semibold text-amber-700">(faltante)</span>
                                @endif

                            </p>

                            @endif



                            @if($unidad->almacenamiento_gb !== null)

                            <p>

                                Disco:

                                {{ $unidad->almacenamiento_gb }}

                                GB

                                {{ $unidad->tipo_almacenamiento }}

                                @if((int) $unidad->almacenamiento_gb === 0)
                                    <span class="font-semibold text-amber-700">(faltante)</span>
                                @endif

                            </p>

                            @endif



                            @if($unidad->tarjeta_grafica)

                            <p>

                                GPU:

                                {{ $unidad->tarjeta_grafica }}

                            </p>

                            @endif



                            @if($unidad->serial_fabricante)

                            <p>

                                Serial:

                                {{ $unidad->serial_fabricante }}

                            </p>

                            @endif

                            @if($unidad->grado_recibido)
                            <p>
                                Grado recibido:
                                <span class="font-semibold">{{ $unidad->grado_recibido }}</span>
                            </p>
                            @endif



                            @if($unidad->tiene_cargador !== null)

                            <p>

                                Cargador:

                                {{ $unidad->tiene_cargador ? 'Sí':'No' }}

                            </p>

                            @endif

                            @if($unidad->requiere_servicio)
                                <p class="font-semibold text-amber-700">
                                    Requiere servicio:
                                    {{ $unidad->servicio_requerido ?: 'Sí' }}
                                </p>
                            @endif



                        </div>


                    </td>





                    {{-- COMPRA --}}

                    <td class="px-6 py-5 text-sm">


                        @if($unidad->precio_compra !== null)


                        <p class="font-semibold">


                            {{ $unidad->precio_compra }}

                            {{ $unidad->moneda?->codigo }}


                        </p>



                        @if($unidad->precio_compra_bob !== null)

                        <p class="text-xs text-green-700">

                            Bs {{ number_format($unidad->precio_compra_bob,2) }}

                        </p>

                        @endif



                        @else


                        <span class="text-xs text-slate-400">

                            Sin compra registrada

                        </span>


                        @endif


                    </td>





                    {{-- ESTADO --}}

                    <td class="px-6 py-5">


                        @php

                        $estadoMostrar = match($unidad->estado){

                        'RECIBIDA_ORIGEN'
                        => 'RECIBIDO',

                        'EN_REVISION'
                        => 'EN REVISIÓN',

                        'EN_PREPARACION'
                        => 'EN PREPARACIÓN',

                        'LISTA_ENVIO'
                        => 'LISTO PARA ENVÍO',

                        'ENVIADA'
                        => 'ENVIADO A ORURO',

                        'RECIBIDA_ORURO'
                        => 'RECIBIDO EN ORURO',

                        'INCORPORADA'
                        => 'INVENTARIO',

                        'ANULADA'
                        => 'ANULADO',

                        default
                        => str_replace('_',' ',$unidad->estado)

                        };

                        @endphp





                        @if(
                        $unidad->estado === \App\Models\UnidadAdquirida::ESTADO_ANULADA
                        )



                        <div class="space-y-2">


                            <span
                                class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">


                                <i data-lucide="circle-x" class="h-3.5 w-3.5">

                                </i>


                                ANULADO


                            </span>




                            @if($unidad->motivo_anulacion)


                            <p class="max-w-xs text-xs text-red-600">

                                {{ $unidad->motivo_anulacion }}

                            </p>


                            @endif



                        </div>




                        @else



                        <span
                            class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">



                            <i data-lucide="circle-check" class="h-3.5 w-3.5">

                            </i>


                            {{ $estadoMostrar }}


                        </span>



                        @endif


                    </td>







                    {{-- ACCIONES --}}

                    <td class="px-6 py-5">

                        @php
                            $puedeModificarRecepcion = in_array(
                                $unidad->estado,
                                [
                                    \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                                    \App\Models\UnidadAdquirida::ESTADO_EN_REVISION,
                                    \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION,
                                ],
                                true
                            );
                        @endphp

                        @if($puedeModificarRecepcion)
                            <div class="flex items-center gap-2">
                                <a
                                    href="{{ route('importaciones.unidades.editar', $unidad) }}"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                >
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    Editar
                                </a>

                                <button
                                    type="button"
                                    onclick="abrirModalAnularUnidad({{ $unidad->id }})"
                                    class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-100"
                                    title="Anular equipo recibido"
                                >
                                    <i data-lucide="ban" class="h-3.5 w-3.5"></i>
                                    Anular
                                </button>
                            </div>
                        @elseif($unidad->estado === \App\Models\UnidadAdquirida::ESTADO_ANULADA)
                            <span class="text-xs text-slate-400">Sin acciones</span>
                        @else
                            <span class="text-xs font-medium text-slate-400">Recepción cerrada</span>
                        @endif

                    </td>







                </tr>



                @empty



                <tr>


                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">


                        Todavía no existen equipos registrados en este lote.


                    </td>


                </tr>


                @endforelse




            </tbody>



        </table>



    </div>



</section>
<div id="modalAnularUnidad" class="
fixed
inset-0
z-50
hidden
bg-black/40
backdrop-blur-sm
p-4
">

    <div class="
        flex
        min-h-screen
        items-center
        justify-center
        px-4
    ">


        <div class="
        w-full
        max-w-lg
        overflow-hidden
        rounded-2xl
        bg-white
        shadow-2xl
        ">


            <div class="border-b border-slate-200 px-5 py-4">

                <h3 class="flex items-center gap-2 text-base font-semibold text-slate-900">

                    <i data-lucide="ban" class="h-5 w-5 text-red-600">
                    </i>

                    Anular equipo recibido

                </h3>


                <p class="mt-1 text-xs text-slate-500">
                    El registro se conservará para auditoría.
                </p>

            </div>





            <form id="formAnularUnidad" method="POST" class="p-5">

                @csrf
                @method('PATCH')



                <label class="text-sm font-semibold text-slate-700">
                    Motivo de anulación
                </label>


                <textarea name="motivo_anulacion" required rows="4" class="
                mt-2
                w-full
                resize-none
                rounded-xl
                border
                border-slate-300
                p-3
                text-sm
                outline-none
                focus:border-red-400
                focus:ring-2
                focus:ring-red-100
                " placeholder="Ejemplo: equipo no disponible, error de recepción..."></textarea>




                <div class="mt-5 flex justify-end gap-3">


                    <button type="button" onclick="cerrarModalAnularUnidad()" class="
                    rounded-xl
                    border
                    px-4
                    py-2
                    text-sm
                    font-medium
                    text-slate-600
                    hover:bg-slate-50
                    ">

                        Cancelar

                    </button>




                    <button type="submit" class="
                    inline-flex
                    items-center
                    gap-2
                    rounded-xl
                    bg-red-600
                    px-4
                    py-2
                    text-sm
                    font-semibold
                    text-white
                    hover:bg-red-700
                    ">

                        <i data-lucide="ban" class="h-4 w-4">
                        </i>

                        Confirmar anulación

                    </button>


                </div>


            </form>


        </div>


    </div>


</div>





@push('scripts')

<script>
    function abrirModalAnularUnidad(id)
{

    const modal = document.getElementById(
        'modalAnularUnidad'
    );


    const formulario = document.getElementById(
        'formAnularUnidad'
    );


    formulario.reset();


    formulario.action =
        "/importaciones/unidades/"
        + id
        + "/anular";


    modal.classList.remove('hidden');

    modal.classList.add(
        'flex',
        'items-center',
        'justify-center'
    );

}



function cerrarModalAnularUnidad()
{

    const modal = document.getElementById(
        'modalAnularUnidad'
    );


    const formulario = document.getElementById(
        'formAnularUnidad'
    );


    formulario.reset();


    modal.classList.add('hidden');

    modal.classList.remove(
        'flex',
        'items-center',
        'justify-center'
    );

}


document.addEventListener(
"DOMContentLoaded",
()=>{

    if(window.lucide)
    {
        lucide.createIcons();
    }

});


</script>

@endpush
