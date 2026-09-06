<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">


    <div class="mb-6">

        <h3 class="text-lg font-semibold text-slate-900">
            Actividad reciente
        </h3>

        <p class="text-sm text-slate-500">
            Últimos movimientos registrados en el sistema
        </p>

    </div>



    <div class="space-y-5">


        @forelse($movimientos as $movimiento)


            <div class="flex gap-4">


                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-blue-600">

                    <x-ui.icon
                        name="activity"
                        size="20"
                    />

                </div>



                <div>


                    <p class="font-medium text-slate-900">

                        {{ $movimiento->equipo->producto->marca->nombre ?? '' }}

                        {{ $movimiento->equipo->producto->modelo ?? '' }}

                    </p>


                    <p class="text-sm text-slate-500">

                        Código:
                        {{ $movimiento->equipo->codigo_interno }}

                    </p>



                    <p class="mt-1 text-sm text-slate-600">


                        @if($movimiento->estadoOrigen && $movimiento->estadoDestino)

                            Cambio:
                            {{ $movimiento->estadoOrigen->nombre }}
                            →
                            {{ $movimiento->estadoDestino->nombre }}

                        @else

                            Movimiento registrado

                        @endif


                    </p>


                    <p class="mt-1 text-xs text-slate-400">

                        {{ optional($movimiento->fecha_cambio)->format('d/m/Y H:i') }}

                    </p>


                </div>


            </div>


        @empty


            <p class="text-sm text-slate-500">

                No existen movimientos registrados todavía.

            </p>


        @endforelse



    </div>


</div>