<div class="bg-white rounded-2xl shadow-oneshop p-6">


    <div class="flex items-center gap-3 mb-6">

        <x-ui.icon
            name="calendar"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Línea de tiempo de trazabilidad
        </h3>

    </div>



    <div class="space-y-6">


        {{-- Registro inicial --}}

        <div class="flex gap-4">


            <div class="mt-1">

                <div class="w-3 h-3 rounded-full bg-oneshop-primary"></div>

            </div>


            <div>

                <p class="font-semibold text-gray-800">
                    Registro de adquisición
                </p>


                <p class="text-sm text-gray-500">

                    {{
                        optional($unidad->created_at)
                        ->format('d/m/Y H:i')
                    }}

                </p>


                <p class="text-sm text-gray-600 mt-1">

                    Unidad registrada dentro del sistema OneShop.

                </p>

            </div>


        </div>




        {{-- Llegada Cochabamba --}}

        @if($unidad->fecha_llegada)

        <div class="flex gap-4">


            <div class="mt-1">

                <div class="w-3 h-3 rounded-full bg-oneshop-primary"></div>

            </div>


            <div>

                <p class="font-semibold text-gray-800">
                    Llegada a Cochabamba
                </p>


                <p class="text-sm text-gray-500">

                    {{
                        \Carbon\Carbon::parse(
                            $unidad->fecha_llegada
                        )->format('d/m/Y H:i')
                    }}

                </p>


                <p class="text-sm text-gray-600 mt-1">

                    Unidad recibida para revisión y preparación.

                </p>

            </div>


        </div>

        @endif





        {{-- Revisión --}}

        @if($unidad->fecha_revision)

        <div class="flex gap-4">


            <div class="mt-1">

                <div class="w-3 h-3 rounded-full bg-oneshop-primary"></div>

            </div>


            <div>

                <p class="font-semibold text-gray-800">
                    Revisión técnica realizada
                </p>


                <p class="text-sm text-gray-500">

                    {{
                        \Carbon\Carbon::parse(
                            $unidad->fecha_revision
                        )->format('d/m/Y H:i')
                    }}

                </p>


                <p class="text-sm text-gray-600 mt-1">

                    Evaluación técnica registrada por el equipo responsable.

                </p>

            </div>


        </div>

        @endif





        {{-- Intervenciones --}}

        @if(
            $unidad->intervenciones &&
            $unidad->intervenciones->count()
        )


            @foreach($unidad->intervenciones as $intervencion)


            <div class="flex gap-4">


                <div class="mt-1">

                    <div class="w-3 h-3 rounded-full bg-oneshop-primary"></div>

                </div>


                <div>


                    <p class="font-semibold text-gray-800">

                        Preparación / intervención técnica

                    </p>


                    <p class="text-sm text-gray-600">

                        {{
                            $intervencion->descripcion
                            ??
                            'Servicio realizado'
                        }}

                    </p>


                </div>


            </div>


            @endforeach


        @endif





        {{-- Incorporación --}}

        @if($unidad->incorporacionInventario)


        <div class="flex gap-4">


            <div class="mt-1">

                <div class="w-3 h-3 rounded-full bg-green-600"></div>

            </div>


            <div>


                <p class="font-semibold text-gray-800">

                    Incorporación a inventario

                </p>


                <p class="text-sm text-gray-500">

                    {{
                        optional(
                            $unidad
                            ->incorporacionInventario
                            ->fecha_incorporacion
                        )
                        ->format('d/m/Y H:i')
                    }}

                </p>


                <p class="text-sm text-gray-600 mt-1">

                    Equipo generado:

                    <strong>

                    {{
                        $unidad->equipo?->codigo_interno
                        ??
                        'Pendiente'

                    }}

                    </strong>

                </p>


            </div>


        </div>


        @endif



    </div>


</div>