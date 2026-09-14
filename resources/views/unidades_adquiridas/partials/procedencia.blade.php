<div class="bg-white rounded-2xl shadow-oneshop p-6">


    <div class="flex items-center gap-3 mb-5">

        <x-ui.icon
            name="truck"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Procedencia de adquisición
        </h3>

    </div>



    <div class="space-y-5">


        @if($unidad->provieneDeLote())

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


                <div>

                    <p class="text-sm text-gray-500">
                        Tipo de adquisición
                    </p>

                    <p class="font-semibold text-gray-800">
                        Compra por lote
                    </p>

                </div>



                <div>

                    <p class="text-sm text-gray-500">
                        Código del lote
                    </p>

                    <p class="font-semibold text-gray-800">

                        {{
                            $unidad
                                ->detalleLote
                                ?->lote
                                ?->codigo
                            ??
                            'Sin código'
                        }}

                    </p>

                </div>



                <div>

                    <p class="text-sm text-gray-500">
                        Proveedor
                    </p>

                    <p class="font-semibold text-gray-800">

                        {{
                            $unidad
                                ->detalleLote
                                ?->lote
                                ?->proveedor
                                ?->nombre
                            ??
                            'Sin proveedor'
                        }}

                    </p>

                </div>



                <div>

                    <p class="text-sm text-gray-500">
                        Origen
                    </p>

                    <p class="font-semibold text-gray-800">

                        {{
                            $unidad
                                ->detalleLote
                                ?->lote
                                ?->origen
                            ??
                            'Sin registro'
                        }}

                    </p>

                </div>


            </div>


        @elseif($unidad->provieneDeAdquisicionDirecta())


            <div>

                <p class="text-sm text-gray-500">
                    Tipo de adquisición
                </p>

                <p class="font-semibold text-gray-800">
                    Compra directa
                </p>

            </div>


        @else


            <div class="text-sm text-gray-500">

                Sin información de procedencia registrada.

            </div>


        @endif


    </div>


</div>