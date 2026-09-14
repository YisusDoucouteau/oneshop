<div class="bg-white rounded-2xl shadow-oneshop p-6">

    <div class="flex items-center gap-3 mb-5">

        <x-ui.icon
            name="file"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Identificación del equipo
        </h3>

    </div>


    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


        <div>
            <p class="text-sm text-gray-500">
                Producto
            </p>

            <p class="font-semibold text-gray-800">
                {{
                    $unidad->producto?->nombre
                    ??
                    $unidad->nombre_equipo
                    ??
                    'Sin registro'
                }}
            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Modelo
            </p>

            <p class="font-semibold text-gray-800">

                {{
                    $unidad->producto?->modelo
                    ??
                    $unidad->modelo_equipo
                    ??
                    'Sin registro'
                }}

            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Código de trazabilidad
            </p>

            <p class="font-semibold text-gray-800">

                {{
                    $unidad->codigo_trazabilidad
                    ??
                    'Sin código'
                }}

            </p>
        </div>



        <div>
            <p class="text-sm text-gray-500">
                Serial fabricante
            </p>

            <p class="font-semibold text-gray-800">

                {{
                    $unidad->serial_fabricante
                    ??
                    'No registrado'
                }}

            </p>
        </div>


    </div>


</div>