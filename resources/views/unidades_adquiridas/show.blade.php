<x-layouts.oneshop
    title="Detalle unidad adquirida | OneShop"
    page-title="Detalle de unidad adquirida"
>

<div class="space-y-6">


    {{-- ============================================================
        MENSAJE DE ÉXITO
    ============================================================ --}}
    @if(session('success'))

        <div
            class="
                flex
                items-start
                gap-3
                rounded-xl
                border
                border-green-200
                bg-green-50
                p-4
                text-green-800
            "
        >
            <x-ui.icon
                name="check"
                size="20"
                class="mt-0.5 shrink-0"
            />

            <span class="text-sm font-medium">
                {{ session('success') }}
            </span>
        </div>

    @endif



    {{-- ============================================================
        ENCABEZADO GENERAL
    ============================================================ --}}
    @include(
        'unidades_adquiridas.partials.encabezado',
        [
            'unidad' => $unidad,
        ]
    )



    {{-- ============================================================
        CONTENIDO PRINCIPAL
    ============================================================ --}}
    <div
        class="
            grid
            grid-cols-1
            gap-6
            xl:grid-cols-3
        "
    >


        {{-- ========================================================
            COLUMNA PRINCIPAL
        ======================================================== --}}
        <div class="space-y-6 xl:col-span-2">


            {{-- Identificación --}}
            @include(
                'unidades_adquiridas.partials.identificacion',
                [
                    'unidad' => $unidad,
                ]
            )



            {{-- Procedencia --}}
            @include(
                'unidades_adquiridas.partials.procedencia',
                [
                    'unidad' => $unidad,
                ]
            )



            {{-- Características técnicas --}}
            @include(
                'unidades_adquiridas.partials.caracteristicas',
                [
                    'unidad' => $unidad,
                ]
            )



            {{-- Preparación / revisión --}}
            @include(
                'unidades_adquiridas.partials.preparacion',
                [
                    'unidad' => $unidad,
                ]
            )



            {{-- Intervenciones --}}
            @include(
                'unidades_adquiridas.partials.intervenciones',
                [
                    'unidad' => $unidad,

                    'productosComponentes' =>
                        $productosComponentes,

                    'monedas' =>
                        $monedas,
                ]
            )


        </div>



        {{-- ========================================================
            COLUMNA LATERAL
        ======================================================== --}}
        <div class="space-y-6">


            {{-- Costos --}}
            @include(
                'unidades_adquiridas.partials.costos',
                [
                    'unidad' => $unidad,
                ]
            )



            {{-- Incorporación al inventario --}}
            @include(
                'unidades_adquiridas.partials.incorporacion',
                [
                    'unidad' =>
                        $unidad,

                    'condicionesFisicas' =>
                        $condicionesFisicas,
                ]
            )


        </div>


    </div>



    {{-- ============================================================
        TRAZABILIDAD
    ============================================================ --}}
    @include(
        'unidades_adquiridas.partials.timeline',
        [
            'unidad' => $unidad,
        ]
    )


</div>

</x-layouts.oneshop>