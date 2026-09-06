<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-950">
            Especificaciones técnicas
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Configuración registrada del equipo.
        </p>

    </div>



    @if($equipo->especificacion)


        <div class="grid gap-x-8 gap-y-6 p-6 sm:grid-cols-2 lg:grid-cols-3">


            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Procesador
                </p>

                <p class="mt-2 font-medium text-slate-900">
                    {{ $equipo->especificacion->procesador ?: '—' }}
                </p>
            </div>



            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Generación
                </p>

                <p class="mt-2 font-medium text-slate-900">
                    {{ $equipo->especificacion->generacion_procesador ?: '—' }}
                </p>
            </div>




            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Memoria RAM
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    @if($equipo->especificacion->ram_gb)

                        {{ $equipo->especificacion->ram_gb }} GB

                    @else

                        —

                    @endif

                </p>
            </div>




            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Almacenamiento
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    @if($equipo->especificacion->almacenamiento_gb)

                        {{ $equipo->especificacion->almacenamiento_gb }} GB

                        {{ $equipo->especificacion->tipo_almacenamiento }}

                    @else

                        —

                    @endif

                </p>
            </div>




            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Tarjeta gráfica
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->especificacion->tarjeta_grafica ?: 'Integrada / no registrada' }}

                </p>
            </div>





            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Pantalla
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    @if($equipo->especificacion->pantalla_pulgadas)

                        {{ $equipo->especificacion->pantalla_pulgadas }}"

                    @else

                        —

                    @endif

                </p>
            </div>





            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Resolución
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->especificacion->resolucion ?: '—' }}

                </p>
            </div>





            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Sistema operativo
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    {{ $equipo->especificacion->sistema_operativo ?: '—' }}

                </p>
            </div>





            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Batería
                </p>

                <p class="mt-2 font-medium text-slate-900">

                    @if($equipo->especificacion->bateria_porcentaje !== null)

                        {{ $equipo->especificacion->bateria_porcentaje }}%

                    @else

                        —

                    @endif

                </p>
            </div>



        </div>



    @else


        <div class="p-10 text-center">


            <p class="font-medium text-slate-800">
                Sin especificaciones registradas
            </p>


            <p class="mt-1 text-sm text-slate-500">
                Este equipo todavía no cuenta con una ficha técnica.
            </p>


        </div>


    @endif


</section>