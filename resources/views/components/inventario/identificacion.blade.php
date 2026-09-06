<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">


    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-950">
            Identificación
        </h2>


        <p class="mt-1 text-sm text-slate-500">
            Información general del equipo físico.
        </p>

    </div>



    <div class="grid gap-x-8 gap-y-6 p-6 sm:grid-cols-2">


        <div>

            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Código interno
            </p>


            <p class="mt-2 font-medium text-slate-900">

                {{ $equipo->codigo_interno }}

            </p>

        </div>



        <div>

            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Serial fabricante
            </p>


            <p class="mt-2 font-medium text-slate-900">

                {{ $equipo->serial_fabricante ?: 'No registrado' }}

            </p>

        </div>



        <div>

            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Categoría
            </p>


            <p class="mt-2 font-medium text-slate-900">

                {{ $equipo->producto?->categoria?->nombre ?? '—' }}

            </p>

        </div>



        <div>

            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Estado actual
            </p>


            <p class="mt-2 font-medium text-slate-900">

                {{ $equipo->estadoActual?->nombre ?? '—' }}

            </p>

        </div>


    </div>



    @if($equipo->observacion)

        <div class="border-t border-slate-100 px-6 py-5">


            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Observación
            </p>


            <p class="mt-2 text-sm leading-6 text-slate-600">

                {{ $equipo->observacion }}

            </p>


        </div>

    @endif


</section>