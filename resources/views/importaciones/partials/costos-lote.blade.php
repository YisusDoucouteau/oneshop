<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    {{-- HEADER --}}
    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-950">
            Costos de importación
        </h2>

        <p class="text-sm text-slate-500">
            Gastos asociados al lote.
        </p>

    </div>





    <div class="p-6">



        @if(
        auth()->user()->tienePermiso('importacion.gestionar')
        )


        <div class="flex flex-wrap gap-3">


            <button type="button" onclick="abrirModalCosto()"
                class="inline-flex items-center rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-slate-800 transition">

                Registrar costo

            </button>

            <form method="POST" action="{{ route('importaciones.costos.distribuir',$lote) }}"
                onsubmit="return confirm('¿Desea distribuir todos los costos activos del lote?')">

                @csrf

                <button type="submit"
                    class="inline-flex items-center rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white shadow hover:bg-indigo-700 transition">

                    Distribuir costos

                </button>
                
            </form>
            
        </div>


        @endif







        <div class="mt-6 overflow-x-auto">


            <table class="min-w-full text-sm">


                <thead>


                    <tr class="border-b bg-slate-50 text-left text-xs uppercase text-slate-500">


                        <th class="px-5 py-4">
                            Tipo
                        </th>


                        <th class="px-5 py-4">
                            Monto origen
                        </th>


                        <th class="px-5 py-4">
                            Equivalente Bs
                        </th>


                        <th class="px-5 py-4">
                            Estado
                        </th>


                        <th class="px-5 py-4">
                            Acciones
                        </th>


                    </tr>


                </thead>





                <tbody class="divide-y divide-slate-100">



                    @forelse($lote->costos as $costo)



                    <tr class="hover:bg-slate-50 transition">



                        {{-- TIPO --}}

                        <td class="px-5 py-5">


                            <p class="font-semibold text-slate-900">

                                {{
                                $costo->tipoCosto?->nombre
                                ?? 'Sin tipo'
                                }}

                            </p>



                            @if($costo->referencia)

                            <p class="text-xs text-slate-500">

                                Ref:
                                {{ $costo->referencia }}

                            </p>

                            @endif


                        </td>






                        {{-- MONTO ORIGEN --}}

                        <td class="px-5 py-5">


                            <p class="font-medium">


                                {{
                                number_format(
                                $costo->monto_origen,
                                2
                                )
                                }}

                                {{
                                $costo->moneda?->codigo
                                }}


                            </p>


                        </td>







                        {{-- BOLIVIANOS --}}

                        <td class="px-5 py-5">


                            <p class="font-semibold text-green-700">


                                Bs

                                {{
                                number_format(
                                $costo->monto_bob,
                                2
                                )
                                }}


                            </p>


                        </td>








                        {{-- ESTADO --}}


                        <td class="px-5 py-5">


                            @if($costo->estado === 'ANULADO')


                            <div class="space-y-1">


                                <span
                                    class="inline-flex items-center gap-2 rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-700">

                                    <i data-lucide="circle-x" class="h-3 w-3">
                                    </i>

                                    ANULADO

                                </span>



                                @if($costo->motivo_anulacion)


                                <p class="max-w-xs text-xs text-red-600">

                                    Motivo:
                                    {{ $costo->motivo_anulacion }}

                                </p>


                                @endif



                            </div>




                            @else



                            <span
                                class="inline-flex items-center gap-2 rounded-full bg-green-100 px-3 py-1 text-xs font-bold text-green-700">


                                <i data-lucide="circle-check" class="h-3 w-3">
                                </i>


                                ACTIVO


                            </span>



                            @endif



                        </td>









                        {{-- ACCIONES --}}


                        <td class="px-5 py-5">


                            @if($costo->estado !== 'ANULADO')



                            <div class="flex items-center gap-2">



                                {{-- EDITAR --}}

                               <button
type="button"
onclick="editarCosto(
{{ $costo->id }},
{{ $costo->tipo_costo_id }},
{{ $costo->moneda_id }},
{{ $costo->monto_origen }},
'{{ $costo->fecha_costo->format('Y-m-d') }}',
{!! json_encode($costo->referencia) !!},
{!! json_encode($costo->observacion) !!},
{{ json_encode($costo->tipoCambio?->valor ?? null) }}
)"
class="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
>

Editar

</button>



                                {{-- DISTRIBUIR --}}

                                

                                {{-- ANULAR --}}


                                <form method="POST" action="{{ 
                                    route(
                                    'importaciones.costos.anular',
                                    $costo
                                    )
                                }}">

                                    @csrf

                                    @method('PATCH')



                                    <input type="hidden" name="motivo_anulacion" value="Costo anulado manualmente">



                                    <button type="submit" onclick="return confirm('¿Está seguro de anular este costo?')"
                                        class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 transition">

                                        Anular

                                    </button>



                                </form>



                            </div>





                            @else



                            <span class="text-xs text-slate-400">

                                Sin acciones

                            </span>



                            @endif



                        </td>





                    </tr>





                    @empty



                    <tr>

                        <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">

                            No existen costos registrados.

                        </td>

                    </tr>



                    @endforelse





                </tbody>


            </table>


        </div>


    </div>


</section>