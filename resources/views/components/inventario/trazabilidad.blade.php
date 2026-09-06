<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-950">
            Historial de trazabilidad
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Seguimiento completo del ciclo de vida del equipo.
        </p>

    </div>



    <div class="p-6">


        @forelse($eventos as $evento)


            <div class="relative flex gap-4 pb-8 last:pb-0">


                @if(!$loop->last)

                    <div class="absolute left-4 top-8 h-full w-px bg-slate-200"></div>

                @endif



                <div class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">


                    <x-ui.icon
                        name="activity"
                        size="16"
                    />


                </div>





                <div class="flex-1">


                    <div class="flex flex-col justify-between gap-2 sm:flex-row">


                        <div>


                            <p class="font-semibold text-slate-900">

                                {{ $evento['titulo'] }}

                            </p>



                            @if($evento['usuario'])

                                <p class="mt-1 text-sm text-slate-500">

                                    Responsable:
                                    {{ $evento['usuario'] }}

                                </p>

                            @endif


                        </div>




                        <span class="text-xs text-slate-400">

                            {{ 
                                \Carbon\Carbon::parse(
                                    $evento['fecha']
                                )->format('d/m/Y H:i')
                            }}

                        </span>



                    </div>





                    @if($evento['detalle'])


                        <p class="mt-3 rounded-lg bg-slate-50 p-3 text-sm text-slate-600">

                            {{ $evento['detalle'] }}

                        </p>


                    @endif





                    @if($evento['observacion'])


                        <p class="mt-2 text-sm text-slate-500">

                            {{ $evento['observacion'] }}

                        </p>


                    @endif



                </div>



            </div>



        @empty


            <div class="py-8 text-center">


                <p class="font-medium text-slate-800">

                    Sin movimientos registrados

                </p>


                <p class="mt-1 text-sm text-slate-500">

                    Este equipo todavía no tiene historial.

                </p>


            </div>


        @endforelse



    </div>


</section>