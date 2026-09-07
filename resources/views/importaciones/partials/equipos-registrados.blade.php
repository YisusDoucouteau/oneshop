<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">


        <div>

            <h2 class="font-semibold text-slate-950">
                Equipos registrados físicamente
            </h2>


            <p class="mt-1 text-sm text-slate-500">
                Equipos identificados por Hugo durante la recepción del lote.
                Todavía no forman parte del inventario.
            </p>

        </div>


     @if(true)   
<button
    type="button"
    onclick="abrirModalEquipo()"
    class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
>

    + Registrar equipo recibido

</button>

@endif


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
                        Estado
                    </th>


                </tr>

            </thead>





            <tbody class="divide-y divide-slate-100">


            @php

                $unidades =
                    $lote->detalles
                    ->pluck('unidadesAdquiridas')
                    ->flatten();

            @endphp




            @forelse($unidades as $unidad)


                <tr>


                    <td class="px-6 py-5">

                        <p class="font-semibold text-slate-900">

                            {{
                                $unidad->codigo_trazabilidad
                                ?? 'Pendiente'
                            }}

                        </p>


                        <p class="text-xs text-slate-500">

                            {{
                                $unidad->created_at
                                ?->format('d/m/Y H:i')
                            }}

                        </p>

                    </td>






                    <td class="px-6 py-5">


                        <p class="font-semibold text-slate-900">


                            {{ $unidad->producto?->marca?->nombre }}

                            {{ $unidad->producto?->nombre }}


                        </p>



                        <p class="text-xs text-slate-500">

                            Modelo:

                            {{ $unidad->producto?->modelo }}

                        </p>


                    </td>







                    <td class="px-6 py-5">


                        <div class="space-y-1 text-sm text-slate-600">


                            @if($unidad->procesador)

                                <p>
                                    CPU:
                                    {{ $unidad->procesador }}
                                </p>

                            @endif



                            @if($unidad->ram_gb)

                                <p>
                                    RAM:
                                    {{ $unidad->ram_gb }} GB
                                </p>

                            @endif



                            @if($unidad->almacenamiento_gb)

                                <p>
                                    Disco:
                                    {{ $unidad->almacenamiento_gb }} GB
                                </p>

                            @endif


                        </div>


                    </td>







                    <td class="px-6 py-5">


                        <span
                            class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700"
                        >

                            {{
                                str_replace(
                                    '_',
                                    ' ',
                                    $unidad->estado
                                )
                            }}

                        </span>


                    </td>



                </tr>



            @empty


                <tr>

                    <td
                        colspan="4"
                        class="px-6 py-12 text-center text-sm text-slate-500"
                    >

                        Todavía no existen equipos registrados en este lote.

                    </td>

                </tr>


            @endforelse



            </tbody>


        </table>


    </div>


</section>