<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">


    <div class="mb-4">

        <h2 class="text-lg font-bold text-slate-900">
            Resumen financiero
        </h2>

        <p class="text-sm text-slate-500">
            Costos activos asociados a la importación.
        </p>

    </div>





    @php


        /*
        |--------------------------------------------------------------------------
        | Solo se consideran costos activos
        |--------------------------------------------------------------------------
        |
        | Los costos anulados permanecen para auditoría,
        | pero no afectan valores financieros.
        |
        */


        $costosActivos = 
            $lote->costos
            ->where(
                'estado',
                'ACTIVO'
            );



        $totalOrigen =
            $costosActivos
            ->sum('monto_origen');



        $totalBob =
            $costosActivos
            ->sum('monto_bob');



        $cantidadCostos =
            $costosActivos
            ->count();



    @endphp







    <div class="grid gap-4 md:grid-cols-3">





        {{-- CANTIDAD --}}

        <div class="rounded-xl bg-slate-50 p-4">


            <p class="text-sm text-slate-500">
                Costos activos
            </p>



            <p class="mt-2 text-2xl font-bold text-slate-900">

                {{ $cantidadCostos }}

            </p>



        </div>









        {{-- ORIGEN --}}


        <div class="rounded-xl bg-slate-50 p-4">


            <p class="text-sm text-slate-500">
                Total origen
            </p>



            <p class="mt-2 text-xl font-bold text-slate-900">


                {{ 
                    number_format(
                        $totalOrigen,
                        2
                    )
                }}



            </p>



        </div>









        {{-- TOTAL BS --}}


        <div class="rounded-xl bg-slate-950 p-4 text-white">


            <p class="text-sm text-slate-300">
                Total inversión Bs
            </p>



            <p class="mt-2 text-2xl font-bold">


                Bs
                {{
                    number_format(
                        $totalBob,
                        2
                    )
                }}



            </p>



        </div>





    </div>




</section>