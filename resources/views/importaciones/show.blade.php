<x-layouts.oneshop
    title="Lote {{ $lote->codigo }} | OneShop"
    page-title="Detalle de importación"
>

@php
    $statusStyles = [
        'ABIERTO' => [
            'label' => 'Abierto',
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-800',
            'border' => 'border-blue-200',
        ],

        'RECEPCION_PARCIAL' => [
            'label' => 'Recepción parcial',
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-800',
            'border' => 'border-amber-200',
        ],

        'RECIBIDO' => [
            'label' => 'Recibido',
            'bg' => 'bg-emerald-50',
            'text' => 'text-emerald-800',
            'border' => 'border-emerald-200',
        ],

        'CERRADO' => [
            'label' => 'Cerrado',
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-700',
            'border' => 'border-slate-200',
        ],

        'ANULADO' => [
            'label' => 'Anulado',
            'bg' => 'bg-red-50',
            'text' => 'text-red-800',
            'border' => 'border-red-200',
        ],
    ];

    $status =
        $statusStyles[$lote->estado]
        ?? [
            'label' => ucfirst(
                strtolower(
                    str_replace('_', ' ', $lote->estado)
                )
            ),
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-700',
            'border' => 'border-slate-200',
        ];
@endphp


<div class="space-y-6">

    {{-- NAVEGACIÓN --}}
    <div>
        <a
            href="{{ route('importaciones.index') }}"
            class="
                inline-flex
                items-center
                gap-2

                rounded-lg
                border
                border-slate-200

                bg-white

                px-3
                py-2

                text-sm
                font-semibold
                text-slate-700

                shadow-sm

                transition
                duration-200

                hover:border-blue-200
                hover:bg-oneshop-light
                hover:text-oneshop-dark

                focus:outline-none
                focus:ring-4
                focus:ring-blue-100
            "
        >
            <span
                class="
                    font-bold
                    text-oneshop-primary
                "
                aria-hidden="true"
            >
                ←
            </span>

            Volver a importaciones
        </a>
    </div>


    {{-- CABECERA DEL LOTE --}}
    <section
        class="
            overflow-hidden

            rounded-2xl
            border
            border-blue-100

            bg-white

            shadow-sm
        "
    >

        {{-- FRANJA SUPERIOR --}}
        <div
            class="
                flex
                flex-col
                gap-5

                border-b
                border-blue-100

                bg-gradient-to-r
                from-blue-50
                via-oneshop-soft
                to-white

                px-6
                py-5

                lg:flex-row
                lg:items-center
                lg:justify-between
            "
        >

            <div
                class="
                    flex
                    min-w-0
                    items-start
                    gap-4
                "
            >

                <div
                    class="
                        flex
                        h-12
                        w-12
                        shrink-0
                        items-center
                        justify-center

                        rounded-xl
                        border
                        border-blue-200

                        bg-white
                        text-oneshop-primary

                        shadow-sm
                    "
                >
                    <x-ui.icon
                        name="package"
                        size="22"
                    />
                </div>


                <div class="min-w-0">

                    <p
                        class="
                            text-xs
                            font-bold
                            uppercase
                            tracking-[0.16em]
                            text-oneshop-primary
                        "
                    >
                        Lote de importación
                    </p>

                    <div
                        class="
                            mt-1
                            flex
                            flex-wrap
                            items-center
                            gap-3
                        "
                    >

                        <h1
                            class="
                                text-2xl
                                font-bold
                                tracking-tight
                                text-slate-950
                            "
                        >
                            {{ $lote->codigo }}
                        </h1>

                        <span
                            class="
                                inline-flex
                                items-center

                                rounded-full
                                border

                                px-3
                                py-1

                                text-xs
                                font-bold

                                {{ $status['bg'] }}
                                {{ $status['text'] }}
                                {{ $status['border'] }}
                            "
                        >
                            {{ $status['label'] }}
                        </span>

                    </div>

                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-500
                        "
                    >
                        {{ $lote->proveedor?->nombre ?? 'Sin proveedor definido' }}
                    </p>

                </div>

            </div>

        </div>


        {{-- DATOS PRINCIPALES --}}
        <div
            class="
                grid
                gap-px

                bg-slate-200

                sm:grid-cols-2
                xl:grid-cols-4
            "
        >

            {{-- FECHA --}}
            <div class="bg-white px-6 py-4">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-slate-500
                    "
                >
                    Fecha de compra
                </p>

                <div
                    class="
                        mt-2
                        flex
                        items-center
                        gap-2
                    "
                >

                    <span class="text-oneshop-primary">
                        <x-ui.icon
                            name="calendar"
                            size="16"
                        />
                    </span>

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-800
                        "
                    >
                        {{ $lote->fecha_compra?->format('d/m/Y') ?? 'No registrada' }}
                    </p>

                </div>

            </div>


            {{-- REFERENCIA --}}
            <div class="bg-white px-6 py-4">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-slate-500
                    "
                >
                    Referencia
                </p>

                <p
                    class="
                        mt-2
                        text-sm
                        font-semibold
                        text-slate-800
                    "
                >
                    {{ $lote->referencia_compra ?: 'Sin referencia' }}
                </p>

            </div>


            {{-- ORIGEN --}}
            <div class="bg-white px-6 py-4">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-slate-500
                    "
                >
                    Origen
                </p>

                <p
                    class="
                        mt-2
                        text-sm
                        font-semibold
                        text-slate-800
                    "
                >
                    {{ $lote->origen ?: 'No especificado' }}
                </p>

            </div>


            {{-- PROVEEDOR --}}
            <div class="bg-white px-6 py-4">

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-slate-500
                    "
                >
                    Proveedor
                </p>

                <p
                    class="
                        mt-2
                        text-sm
                        font-semibold
                        text-slate-800
                    "
                >
                    {{ $lote->proveedor?->nombre ?? 'Sin proveedor' }}
                </p>

            </div>

        </div>

    </section>


    {{-- COMPOSICIÓN DEL LOTE --}}
    @include('importaciones.partials.detalles-lote')


    {{-- EQUIPOS REGISTRADOS --}}
    @include('importaciones.partials.equipos-registrados')


    {{-- COSTOS --}}
    @include('importaciones.partials.costos-lote')


    {{-- RESUMEN FINANCIERO --}}
    @include('importaciones.partials.resumen-financiero')


    {{-- MODALES --}}
    @include('importaciones.partials.modal-costo')

    @include('importaciones.partials.modal-editar-costo')

    @include('importaciones.partials.modal-producto')

    @include('importaciones.partials.modal-registrar-equipo')

</div>

</x-layouts.oneshop>