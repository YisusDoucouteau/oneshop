<x-layouts.oneshop
    title="Detalle unidad adquirida | OneShop"
    page-title="Detalle de unidad adquirida"
>

<div class="space-y-6">


    @include(
        'unidades_adquiridas.partials.encabezado',
        [
            'unidad' => $unidad
        ]
    )


    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">


        <div class="xl:col-span-2 space-y-6">


            @include(
                'unidades_adquiridas.partials.identificacion',
                [
                    'unidad' => $unidad
                ]
            )


            @include(
                'unidades_adquiridas.partials.procedencia',
                [
                    'unidad' => $unidad
                ]
            )


            @include(
                'unidades_adquiridas.partials.caracteristicas',
                [
                    'unidad' => $unidad
                ]
            )


            @include(
                'unidades_adquiridas.partials.preparacion',
                [
                    'unidad' => $unidad
                ]
            )


        </div>



        <div class="space-y-6">


            @include(
                'unidades_adquiridas.partials.costos',
                [
                    'unidad' => $unidad
                ]
            )


            @include(
                'unidades_adquiridas.partials.incorporacion',
                [
                    'unidad' => $unidad
                ]
            )


        </div>


    </div>



    @include(
        'unidades_adquiridas.partials.timeline',
        [
            'unidad' => $unidad
        ]
    )


</div>


</x-layouts.oneshop>