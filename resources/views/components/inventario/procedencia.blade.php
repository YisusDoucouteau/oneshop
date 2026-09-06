<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-950">
            Procedencia del equipo
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Información relacionada con la importación y origen del equipo.
        </p>

    </div>



    @if($equipo->detalleLote)


        <div class="grid gap-x-8 gap-y-6 p-6 sm:grid-cols-2 lg:grid-cols-3">



            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Código de lote
                </p>


                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->detalleLote->lote?->codigo ?? '—' }}

                </p>

            </div>




            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Proveedor
                </p>


                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->detalleLote->lote?->proveedor?->nombre ?? '—' }}

                </p>

            </div>





            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Fecha ingreso
                </p>


                <p class="mt-2 font-medium text-slate-900">

                    {{ 
                        optional($equipo->detalleLote->lote?->fecha_ingreso)
                        ?->format('d/m/Y') 
                        ?? '—'
                    }}

                </p>

            </div>





            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Cantidad del lote
                </p>


                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->detalleLote->cantidad ?? '—' }}

                    unidades

                </p>

            </div>





            <div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Número de serie
                </p>


                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->serial_fabricante ?: 'Sin serial' }}

                </p>

            </div>



        </div>



    @else


        <div class="p-8 text-center">

            <p class="font-medium text-slate-800">

                Equipo sin lote asociado

            </p>


            <p class="mt-1 text-sm text-slate-500">

                La unidad todavía no tiene información de importación registrada.

            </p>


        </div>


    @endif


</section>