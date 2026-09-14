<div class="bg-white rounded-2xl shadow-oneshop p-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        <div>

            <div class="flex items-center gap-3">

                <x-ui.icon
                    name="package"
                    class="text-oneshop-primary"
                />

                <h2 class="text-2xl font-bold text-gray-800">
                    {{
                        $unidad->producto?->nombre
                        ??
                        $unidad->nombre_equipo
                        ??
                        'Equipo sin nombre'
                    }}
                </h2>

            </div>


            <p class="text-sm text-gray-500 mt-2">

                Código trazabilidad:

                <span class="font-semibold text-gray-700">
                    {{
                        $unidad->codigo_trazabilidad
                        ??
                        'Sin código'
                    }}
                </span>

            </p>

        </div>


        <span
            class="
            px-4
            py-2
            rounded-xl
            text-sm
            font-semibold
            bg-oneshop-light
            text-oneshop-dark
            "
        >
            {{ $unidad->estado }}
        </span>


    </div>

</div>