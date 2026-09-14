<div class="bg-white rounded-2xl shadow-oneshop p-6">


    <div class="flex items-center gap-3 mb-5">

        <x-ui.icon
            name="chart"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Información de costos
        </h3>

    </div>



    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">


        <div>

            <p class="text-sm text-gray-500">
                Precio compra
            </p>

            <p class="font-semibold text-gray-800">

                @if($unidad->precio_compra)

                    {{ number_format($unidad->precio_compra,2) }}

                    {{
                        $unidad->moneda?->codigo
                        ??
                        ''
                    }}

                @else

                    No registrado

                @endif

            </p>

        </div>



        <div>

            <p class="text-sm text-gray-500">
                Costo equivalente BOB
            </p>

            <p class="font-semibold text-gray-800">

                @if($unidad->precio_compra_bob)

                    Bs.
                    {{ number_format($unidad->precio_compra_bob,2) }}

                @else

                    No calculado

                @endif

            </p>

        </div>



        <div>

            <p class="text-sm text-gray-500">
                Tipo de cambio aplicado
            </p>

            <p class="font-semibold text-gray-800">

                {{
                    $unidad->tipoCambioCompra?->valor
                    ??
                    'No registrado'
                }}

            </p>

        </div>


    </div>



    <div class="mt-6">


        <h4 class="font-semibold text-gray-800 mb-3">

            Costos de preparación

        </h4>



        @if(
            $unidad->costosPreparacion &&
            $unidad->costosPreparacion->count()
        )


            <div class="space-y-3">


                @foreach($unidad->costosPreparacion as $costo)


                    <div
                        class="
                        border
                        rounded-xl
                        p-4
                        flex
                        justify-between
                        items-center
                        "
                    >


                        <div>

                            <p class="font-medium text-gray-800">

                                {{
                                    $costo->tipoCosto?->nombre
                                    ??
                                    'Costo adicional'
                                }}

                            </p>


                            @if($costo->observacion)

                                <p class="text-sm text-gray-500">

                                    {{ $costo->observacion }}

                                </p>

                            @endif


                        </div>



                        <div class="font-semibold text-gray-800">


                            {{
                                number_format(
                                    $costo->monto,
                                    2
                                )
                            }}

                            {{
                                $costo->moneda?->codigo
                                ??
                                'BOB'
                            }}


                        </div>


                    </div>


                @endforeach


            </div>


        @else


            <div class="bg-gray-50 rounded-xl p-4 text-gray-500">

                No existen costos adicionales registrados.

            </div>


        @endif


    </div>


</div>