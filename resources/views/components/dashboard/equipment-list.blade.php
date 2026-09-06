<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

    <div class="mb-6">
        <h3 class="text-lg font-semibold text-slate-900">
            Últimos equipos registrados
        </h3>

        <p class="mt-1 text-sm text-slate-500">
            Equipos incorporados recientemente al inventario OneShop
        </p>
    </div>


    <div class="space-y-4">

        @forelse($equipos as $equipo)

            <div class="flex items-center justify-between rounded-xl border border-slate-100 p-4 transition hover:bg-slate-50">

                <div class="flex items-center gap-4">


                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">

                        <x-ui.icon
                            name="package"
                            size="22"
                        />

                    </div>



                    <div>

                        <p class="font-semibold text-slate-900">

                            {{ $equipo->producto->marca->nombre ?? 'Sin marca' }}

                            {{ $equipo->producto->modelo ?? 'Sin modelo' }}

                        </p>


                        <p class="text-sm text-slate-500">

                            Código:
                            <span class="font-medium">
                                {{ $equipo->codigo_interno }}
                            </span>

                        </p>


                        @if($equipo->serial_fabricante)

                            <p class="text-xs text-slate-400">

                                Serial:
                                {{ $equipo->serial_fabricante }}

                            </p>

                        @endif


                    </div>


                </div>



                <div class="text-right">


                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">

                        {{ $equipo->estadoActual->nombre ?? 'Sin estado' }}

                    </span>


                    <p class="mt-2 text-xs text-slate-400">

                        {{ optional($equipo->fecha_registro)->format('d/m/Y') }}

                    </p>


                </div>


            </div>


        @empty

            <div class="rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">

                No existen equipos registrados todavía.

            </div>

        @endforelse


    </div>


</div>