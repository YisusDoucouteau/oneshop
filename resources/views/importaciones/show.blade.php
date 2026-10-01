<x-layouts.oneshop
    title="Lote {{ $lote->codigo }} | OneShop"
    page-title="Detalle de importación"
>

@php

    /*
    |--------------------------------------------------------------------------
    | ESTADO VISUAL
    |--------------------------------------------------------------------------
    */

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
                    str_replace(
                        '_',
                        ' ',
                        $lote->estado
                    )
                )
            ),

            'bg' => 'bg-slate-100',
            'text' => 'text-slate-700',
            'border' => 'border-slate-200',
        ];


    /*
    |--------------------------------------------------------------------------
    | RESUMEN OPERATIVO
    |--------------------------------------------------------------------------
    */

    $totalEsperadas =
        $lote
            ->detalles
            ->sum(
                fn ($detalle) =>
                    (int) $detalle->cantidad_esperada
            );


    $totalRecibidas =
        $lote
            ->detalles
            ->sum(
                fn ($detalle) =>
                    $detalle
                        ->unidadesAdquiridas
                        ->where(
                            'estado',
                            '!=',
                            \App\Models\UnidadAdquirida::ESTADO_ANULADA
                        )
                        ->count()
            );


    $totalPendientes =
        max(
            0,
            $totalEsperadas - $totalRecibidas
        );


    $porcentajeRecepcion =
        $totalEsperadas > 0

            ? min(
                100,
                (int) round(
                    (
                        $totalRecibidas
                        / $totalEsperadas
                    )
                    * 100
                )
            )

            : 0;


    $puedeGestionar =
        auth()
            ->user()
            ->tienePermiso(
                'importacion.gestionar'
            );

@endphp


<div class="space-y-6">

    {{-- ================================================================ --}}
    {{-- VOLVER --}}
    {{-- ================================================================ --}}

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
            >
                ←
            </span>

            Volver a importaciones

        </a>

    </div>


    {{-- ================================================================ --}}
    {{-- MENSAJES --}}
    {{-- ================================================================ --}}

    @if(
        session('success')
        && ! request()->boolean('registrar_equipo')
    )

        <div
            class="
                rounded-xl
                border
                border-emerald-200
                bg-emerald-50
                px-4
                py-3
            "
        >

            <div
                class="
                    flex
                    items-center
                    gap-3
                "
            >

                <div
                    class="
                        flex
                        h-8
                        w-8
                        shrink-0
                        items-center
                        justify-center

                        rounded-lg

                        bg-white
                        text-emerald-700
                    "
                >
                    <x-ui.icon
                        name="check"
                        size="15"
                    />
                </div>

                <p
                    class="
                        text-sm
                        font-semibold
                        text-emerald-900
                    "
                >
                    {{ session('success') }}
                </p>

            </div>

        </div>

    @endif


    @if(
        $errors->any()
        && old('_form_context')
            !== 'recepcion_unidad'
    )

        <div
            class="
                rounded-xl
                border
                border-red-200
                bg-red-50
                p-4
            "
        >

            <p
                class="
                    text-sm
                    font-bold
                    text-red-900
                "
            >
                Revisa la operación solicitada
            </p>

            <ul
                class="
                    mt-2
                    list-inside
                    list-disc
                    space-y-1

                    text-sm
                    text-red-800
                "
            >
                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach
            </ul>

        </div>

    @endif


    {{-- ================================================================ --}}
    {{-- CABECERA DEL LOTE --}}
    {{-- ================================================================ --}}

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
                            font-medium
                            text-slate-600
                        "
                    >
                        {{ $lote->proveedor?->nombre ?? 'Sin proveedor definido' }}
                    </p>

                </div>

            </div>


            {{-- PROGRESO COMPACTO --}}
            <div
                class="
                    w-full

                    lg:w-64
                "
            >

                <div
                    class="
                        mb-2

                        flex
                        items-center
                        justify-between

                        text-xs
                    "
                >

                    <span
                        class="
                            font-semibold
                            text-slate-500
                        "
                    >
                        Recepción física
                    </span>

                    <span
                        class="
                            font-bold
                            text-slate-800
                        "
                    >
                        {{ $porcentajeRecepcion }}%
                    </span>

                </div>


                <div
                    class="
                        h-2
                        overflow-hidden
                        rounded-full
                        bg-blue-100
                    "
                >

                    <div
                        class="
                            h-full
                            rounded-full
                            bg-oneshop-primary
                        "

                        style="
                            width:
                            {{ $porcentajeRecepcion }}%
                        "
                    ></div>

                </div>

            </div>

        </div>


        {{-- DATOS DEL LOTE --}}
        <div
            class="
                grid
                gap-px

                bg-slate-200

                sm:grid-cols-2
                xl:grid-cols-4
            "
        >

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

                <p
                    class="
                        mt-2
                        text-sm
                        font-semibold
                        text-slate-800
                    "
                >
                    {{ $lote->fecha_compra?->format('d/m/Y') ?? 'No registrada' }}
                </p>

            </div>


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


    {{-- ================================================================ --}}
    {{-- RESUMEN OPERATIVO --}}
    {{-- ================================================================ --}}

    <div
        class="
            grid
            gap-4

            md:grid-cols-3
        "
    >

        {{-- ESPERADAS --}}
        <div
            class="
                rounded-2xl
                border
                border-slate-200

                bg-white

                p-5

                shadow-sm
            "
        >

            <p
                class="
                    text-xs
                    font-bold
                    uppercase
                    tracking-wide
                    text-slate-500
                "
            >
                Unidades esperadas
            </p>

            <p
                class="
                    mt-2
                    text-3xl
                    font-bold
                    text-slate-950
                "
            >
                {{ $totalEsperadas }}
            </p>

            <p
                class="
                    mt-1
                    text-xs
                    text-slate-500
                "
            >
                Según composición del lote
            </p>

        </div>


        {{-- RECIBIDAS --}}
        <div
            class="
                rounded-2xl
                border
                border-emerald-200

                bg-emerald-50

                p-5

                shadow-sm
            "
        >

            <p
                class="
                    text-xs
                    font-bold
                    uppercase
                    tracking-wide
                    text-emerald-700
                "
            >
                Unidades recibidas
            </p>

            <p
                class="
                    mt-2
                    text-3xl
                    font-bold
                    text-emerald-900
                "
            >
                {{ $totalRecibidas }}
            </p>

            <p
                class="
                    mt-1
                    text-xs
                    text-emerald-700
                "
            >
                Recepción física activa
            </p>

        </div>


        {{-- PENDIENTES --}}
        <div
            class="
                rounded-2xl
                border

                {{ $totalPendientes > 0
                    ? 'border-amber-200 bg-amber-50'
                    : 'border-emerald-200 bg-emerald-50'
                }}

                p-5

                shadow-sm
            "
        >

            <p
                class="
                    text-xs
                    font-bold
                    uppercase
                    tracking-wide

                    {{ $totalPendientes > 0
                        ? 'text-amber-700'
                        : 'text-emerald-700'
                    }}
                "
            >
                Pendientes
            </p>

            <p
                class="
                    mt-2
                    text-3xl
                    font-bold

                    {{ $totalPendientes > 0
                        ? 'text-amber-900'
                        : 'text-emerald-900'
                    }}
                "
            >
                {{ $totalPendientes }}
            </p>

            <p
                class="
                    mt-1
                    text-xs

                    {{ $totalPendientes > 0
                        ? 'text-amber-700'
                        : 'text-emerald-700'
                    }}
                "
            >
                {{ $totalPendientes > 0
                    ? 'Unidades aún por recibir'
                    : 'Recepción física completa'
                }}
            </p>

        </div>

    </div>


    {{-- ================================================================ --}}
    {{-- BARRA OPERATIVA --}}
    {{-- ================================================================ --}}

    @if($puedeGestionar)

        <section
            class="
                rounded-2xl
                border
                border-blue-100

                bg-gradient-to-r
                from-blue-50
                via-oneshop-soft
                to-white

                p-4

                shadow-sm
            "
        >

            <div
                class="
                    flex
                    flex-col
                    gap-4

                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                "
            >

                <div>

                    <p
                        class="
                            text-xs
                            font-bold
                            uppercase
                            tracking-[0.14em]
                            text-oneshop-primary
                        "
                    >
                        Acciones del lote
                    </p>

                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-600
                        "
                    >
                        Registra la operación que necesitas realizar.
                    </p>

                </div>


                <div
                    class="
                        flex
                        flex-col
                        gap-2

                        sm:flex-row
                        sm:flex-wrap
                    "
                >

                    {{-- REGISTRAR EQUIPO --}}
                    <button
                        type="button"

                        @if($totalPendientes > 0)
                            onclick="abrirModalEquipo()"
                        @endif

                        @disabled($totalPendientes <= 0)

                        class="
                            inline-flex
                            items-center
                            justify-center
                            gap-2

                            rounded-xl
                            border

                            px-4
                            py-2.5

                            text-sm
                            font-bold

                            transition

                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100

                            {{ $totalPendientes > 0
                                ? '
                                    border-oneshop-primary
                                    bg-oneshop-light
                                    text-oneshop-dark
                                    shadow-sm
                                    hover:bg-blue-100
                                '
                                : '
                                    cursor-not-allowed
                                    border-slate-200
                                    bg-slate-100
                                    text-slate-400
                                '
                            }}
                        "
                    >

                        <x-ui.icon
                            name="plus"
                            size="16"
                        />

                        {{ $totalPendientes > 0
                            ? 'Registrar equipo recibido'
                            : 'Recepción completa'
                        }}

                    </button>


                    {{-- AGREGAR PRODUCTO --}}
                    <button
                        type="button"

                        onclick="
                            abrirModalAgregarProductoLote()
                        "

                        class="
                            inline-flex
                            items-center
                            justify-center
                            gap-2

                            rounded-xl
                            border
                            border-blue-200

                            bg-white

                            px-4
                            py-2.5

                            text-sm
                            font-bold
                            text-oneshop-dark

                            shadow-sm

                            transition

                            hover:bg-blue-50

                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100
                        "
                    >

                        <x-ui.icon
                            name="plus"
                            size="16"
                        />

                        Agregar producto

                    </button>


                    {{-- REGISTRAR COSTO --}}
                    <button
                        type="button"

                        onclick="
                            abrirModalCosto()
                        "

                        class="
                            inline-flex
                            items-center
                            justify-center
                            gap-2

                            rounded-xl
                            border
                            border-slate-300

                            bg-white

                            px-4
                            py-2.5

                            text-sm
                            font-bold
                            text-slate-700

                            shadow-sm

                            transition

                            hover:border-blue-200
                            hover:bg-blue-50
                            hover:text-oneshop-dark

                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100
                        "
                    >

                        <x-ui.icon
                            name="plus"
                            size="16"
                        />

                        Registrar costo

                    </button>

                </div>

            </div>

        </section>

    @endif


    {{-- ================================================================ --}}
    {{-- NAVEGACIÓN INTERNA --}}
    {{-- ================================================================ --}}

    <nav
        class="
            flex
            gap-2
            overflow-x-auto

            rounded-xl
            border
            border-slate-200

            bg-white

            p-2

            shadow-sm
        "
        aria-label="Secciones del lote"
    >

        <a
            href="#equipos-recibidos"

            class="
                whitespace-nowrap

                rounded-lg

                px-3
                py-2

                text-sm
                font-semibold
                text-slate-700

                transition

                hover:bg-blue-50
                hover:text-oneshop-dark
            "
        >
            Equipos recibidos
        </a>


        <a
            href="#composicion-lote"

            class="
                whitespace-nowrap

                rounded-lg

                px-3
                py-2

                text-sm
                font-semibold
                text-slate-700

                transition

                hover:bg-blue-50
                hover:text-oneshop-dark
            "
        >
            Composición
        </a>


        <a
            href="#costos-lote"

            class="
                whitespace-nowrap

                rounded-lg

                px-3
                py-2

                text-sm
                font-semibold
                text-slate-700

                transition

                hover:bg-blue-50
                hover:text-oneshop-dark
            "
        >
            Costos
        </a>


        <a
            href="#resumen-financiero"

            class="
                whitespace-nowrap

                rounded-lg

                px-3
                py-2

                text-sm
                font-semibold
                text-slate-700

                transition

                hover:bg-blue-50
                hover:text-oneshop-dark
            "
        >
            Resumen financiero
        </a>

    </nav>


    {{-- ================================================================ --}}
    {{-- 1. EQUIPOS RECIBIDOS --}}
    {{-- ================================================================ --}}

    <div
        id="equipos-recibidos"
        class="scroll-mt-6"
    >
        @include(
            'importaciones.partials.equipos-registrados'
        )
    </div>


    {{-- ================================================================ --}}
    {{-- 2. COMPOSICIÓN --}}
    {{-- ================================================================ --}}

    <div
        id="composicion-lote"
        class="scroll-mt-6"
    >
        @include(
            'importaciones.partials.detalles-lote'
        )
    </div>


    {{-- ================================================================ --}}
    {{-- 3. COSTOS --}}
    {{-- ================================================================ --}}

    <div
        id="costos-lote"
        class="scroll-mt-6"
    >
        @include(
            'importaciones.partials.costos-lote'
        )
    </div>


    {{-- ================================================================ --}}
    {{-- 4. RESUMEN FINANCIERO --}}
    {{-- ================================================================ --}}

    <div
        id="resumen-financiero"
        class="scroll-mt-6"
    >
        @include(
            'importaciones.partials.resumen-financiero'
        )
    </div>


    {{-- ================================================================ --}}
    {{-- MODALES --}}
    {{-- ================================================================ --}}

    @include(
        'importaciones.partials.modal-agregar-producto-lote'
    )

    @include(
        'importaciones.partials.modal-producto'
    )

    @include(
        'importaciones.partials.modal-costo'
    )

    @include(
        'importaciones.partials.modal-editar-costo'
    )

    @include(
        'importaciones.partials.modal-registrar-equipo'
    )

</div>

</x-layouts.oneshop>