<div class="bg-white rounded-2xl shadow-oneshop p-6">


    <div class="flex items-center gap-3 mb-5">

        <x-ui.icon
            name="settings"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Características técnicas
        </h3>

    </div>



    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">


        <div>
            <p class="text-sm text-gray-500">
                Procesador
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->procesador
                    ??
                    'No registrado'
                }}
            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Generación
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->generacion_procesador
                    ??
                    'No registrada'
                }}
            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Memoria RAM
            </p>

            <p class="font-semibold text-gray-800">

                @if($unidad->ram_gb)

                    {{ $unidad->ram_gb }} GB

                @else

                    No registrada

                @endif

            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Almacenamiento
            </p>

            <p class="font-semibold text-gray-800">

                @if($unidad->almacenamiento_gb)

                    {{ $unidad->almacenamiento_gb }} GB

                    {{
                        $unidad->tipo_almacenamiento
                        ?
                        strtoupper(
                            ' '.$unidad->tipo_almacenamiento
                        )
                        :
                        ''
                    }}

                @else

                    No registrado

                @endif

            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Tarjeta gráfica
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->tarjeta_grafica
                    ??
                    'No registrada'
                }}
            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Pantalla
            </p>

            <p class="font-semibold text-gray-800">

                {{
                    $unidad->pantalla_pulgadas
                    ?
                    $unidad->pantalla_pulgadas.' pulgadas'
                    :
                    'No registrada'
                }}

            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Resolución
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->resolucion
                    ??
                    'No registrada'
                }}
            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Sistema operativo
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->sistema_operativo
                    ??
                    'No registrado'
                }}
            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Grado final
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->grado_final
                    ??
                    'Pendiente'
                }}
            </p>
        </div>


    </div>


</div>