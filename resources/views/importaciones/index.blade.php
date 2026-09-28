<x-layouts.oneshop
    title="Importaciones | OneShop"
    page-title="Importaciones"
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
@endphp


<div class="space-y-6">

    {{-- ENCABEZADO --}}
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
                    tracking-[0.16em]
                    text-oneshop-primary
                "
            >
                Compras e importación
            </p>

            <h1
                class="
                    mt-1
                    text-2xl
                    font-bold
                    tracking-tight
                    text-slate-950
                "
            >
                Lotes de importación
            </h1>

            <p
                class="
                    mt-1
                    max-w-3xl
                    text-sm
                    leading-6
                    text-slate-500
                "
            >
                Control de compras, composición, recepción física
                y procedencia de los equipos importados.
            </p>

        </div>


        @if(auth()->user()->tienePermiso('importacion.gestionar'))

            <a
                href="{{ route('importaciones.create') }}"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2

                    rounded-xl
                    border
                    border-oneshop-primary

                    bg-oneshop-light

                    px-4
                    py-2.5

                    text-sm
                    font-bold
                    text-oneshop-dark

                    transition

                    hover:bg-blue-100

                    focus:outline-none
                    focus:ring-4
                    focus:ring-blue-100
                "
            >
                <x-ui.icon
                    name="plus"
                    size="17"
                />

                Nuevo lote
            </a>

        @endif

    </div>


    {{-- MÉTRICAS --}}
    <div
        class="
            grid
            gap-4
            sm:grid-cols-2
            xl:grid-cols-4
        "
    >

        {{-- TOTAL --}}
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

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-4
                "
            >

                <div>

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                        "
                    >
                        Total de lotes
                    </p>

                    <p
                        class="
                            mt-2
                            text-3xl
                            font-bold
                            tracking-tight
                            text-slate-950
                        "
                    >
                        {{ $resumen['total'] }}
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-500
                        "
                    >
                        Lotes registrados
                    </p>

                </div>


                <div
                    class="
                        flex
                        h-11
                        w-11
                        shrink-0
                        items-center
                        justify-center

                        rounded-xl
                        border
                        border-blue-100

                        bg-oneshop-light
                        text-oneshop-primary
                    "
                >
                    <x-ui.icon
                        name="package"
                        size="21"
                    />
                </div>

            </div>

        </div>


        {{-- ABIERTOS --}}
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

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-4
                "
            >

                <div>

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                        "
                    >
                        Abiertos
                    </p>

                    <p
                        class="
                            mt-2
                            text-3xl
                            font-bold
                            tracking-tight
                            text-slate-950
                        "
                    >
                        {{ $resumen['abiertos'] }}
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-500
                        "
                    >
                        Pendientes de completar
                    </p>

                </div>


                <div
                    class="
                        flex
                        h-11
                        w-11
                        shrink-0
                        items-center
                        justify-center

                        rounded-xl
                        border
                        border-blue-200

                        bg-blue-50
                        text-blue-700
                    "
                >
                    <x-ui.icon
                        name="file"
                        size="21"
                    />
                </div>

            </div>

        </div>


        {{-- RECEPCIÓN PARCIAL --}}
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

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-4
                "
            >

                <div>

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                        "
                    >
                        Recepción parcial
                    </p>

                    <p
                        class="
                            mt-2
                            text-3xl
                            font-bold
                            tracking-tight
                            text-slate-950
                        "
                    >
                        {{ $resumen['parciales'] }}
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-500
                        "
                    >
                        Con recepción iniciada
                    </p>

                </div>


                <div
                    class="
                        flex
                        h-11
                        w-11
                        shrink-0
                        items-center
                        justify-center

                        rounded-xl
                        border
                        border-amber-200

                        bg-amber-50
                        text-amber-700
                    "
                >
                    <x-ui.icon
                        name="truck"
                        size="21"
                    />
                </div>

            </div>

        </div>


        {{-- RECIBIDOS --}}
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

            <div
                class="
                    flex
                    items-start
                    justify-between
                    gap-4
                "
            >

                <div>

                    <p
                        class="
                            text-sm
                            font-semibold
                            text-slate-600
                        "
                    >
                        Recibidos
                    </p>

                    <p
                        class="
                            mt-2
                            text-3xl
                            font-bold
                            tracking-tight
                            text-slate-950
                        "
                    >
                        {{ $resumen['recibidos'] }}
                    </p>

                    <p
                        class="
                            mt-1
                            text-xs
                            text-slate-500
                        "
                    >
                        Recepción completada
                    </p>

                </div>


                <div
                    class="
                        flex
                        h-11
                        w-11
                        shrink-0
                        items-center
                        justify-center

                        rounded-xl
                        border
                        border-emerald-200

                        bg-emerald-50
                        text-emerald-700
                    "
                >
                    <x-ui.icon
                        name="check"
                        size="21"
                    />
                </div>

            </div>

        </div>

    </div>


    {{-- FILTROS --}}
    <form
        method="GET"
        action="{{ route('importaciones.index') }}"
        class="
            rounded-2xl
            border
            border-slate-200
            bg-white
            p-5
            shadow-sm
        "
    >

        <div
            class="
                mb-4
                flex
                items-center
                gap-3
            "
        >

            <div
                class="
                    flex
                    h-9
                    w-9
                    items-center
                    justify-center

                    rounded-lg
                    bg-oneshop-light
                    text-oneshop-primary
                "
            >
                <x-ui.icon
                    name="filter"
                    size="18"
                />
            </div>


            <div>

                <p
                    class="
                        text-sm
                        font-bold
                        text-slate-900
                    "
                >
                    Buscar y filtrar
                </p>

                <p
                    class="
                        text-xs
                        text-slate-500
                    "
                >
                    Encuentra lotes por sus datos principales.
                </p>

            </div>

        </div>


        <div
            class="
                grid
                gap-4
                lg:grid-cols-[minmax(0,1fr)_240px_auto]
            "
        >

            {{-- BUSCAR --}}
            <div>

                <label
                    for="buscar"
                    class="
                        mb-2
                        block
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-slate-600
                    "
                >
                    Buscar
                </label>

                <div class="relative">

                    <div
                        class="
                            pointer-events-none
                            absolute
                            inset-y-0
                            left-0

                            flex
                            items-center

                            pl-3.5

                            text-slate-400
                        "
                    >
                        <x-ui.icon
                            name="search"
                            size="17"
                        />
                    </div>

                    <input
                        id="buscar"
                        type="text"
                        name="buscar"
                        value="{{ $buscar }}"
                        placeholder="Código, proveedor, referencia u origen..."
                        class="
                            w-full
                            rounded-xl
                            border
                            border-slate-300
                            bg-white

                            py-2.5
                            pl-10
                            pr-3.5

                            text-sm
                            text-slate-900

                            placeholder:text-slate-400

                            focus:border-oneshop-primary
                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100
                        "
                    >

                </div>

            </div>


            {{-- ESTADO --}}
            <div>

                <label
                    for="estado"
                    class="
                        mb-2
                        block
                        text-xs
                        font-bold
                        uppercase
                        tracking-wide
                        text-slate-600
                    "
                >
                    Estado
                </label>

                <select
                    id="estado"
                    name="estado"
                    class="
                        w-full
                        rounded-xl
                        border
                        border-slate-300
                        bg-white

                        px-3.5
                        py-2.5

                        text-sm
                        text-slate-900

                        focus:border-oneshop-primary
                        focus:outline-none
                        focus:ring-4
                        focus:ring-blue-100
                    "
                >

                    <option value="">
                        Todos los estados
                    </option>

                    <option
                        value="ABIERTO"
                        @selected($estado === 'ABIERTO')
                    >
                        Abierto
                    </option>

                    <option
                        value="RECEPCION_PARCIAL"
                        @selected($estado === 'RECEPCION_PARCIAL')
                    >
                        Recepción parcial
                    </option>

                    <option
                        value="RECIBIDO"
                        @selected($estado === 'RECIBIDO')
                    >
                        Recibido
                    </option>

                    <option
                        value="CERRADO"
                        @selected($estado === 'CERRADO')
                    >
                        Cerrado
                    </option>

                </select>

            </div>


            {{-- ACCIONES --}}
            <div
                class="
                    flex
                    flex-wrap
                    items-end
                    gap-2
                "
            >

                <button
                    type="submit"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2

                        rounded-xl
                        border
                        border-oneshop-primary

                        bg-oneshop-light

                        px-4
                        py-2.5

                        text-sm
                        font-bold
                        text-oneshop-dark

                        transition

                        hover:bg-blue-100

                        focus:outline-none
                        focus:ring-4
                        focus:ring-blue-100
                    "
                >
                    <x-ui.icon
                        name="filter"
                        size="16"
                    />

                    Filtrar
                </button>


                @if(filled($buscar) || filled($estado))

                    <a
                        href="{{ route('importaciones.index') }}"
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
                            font-semibold
                            text-slate-700

                            transition

                            hover:border-slate-400
                            hover:bg-slate-50

                            focus:outline-none
                            focus:ring-4
                            focus:ring-slate-100
                        "
                    >
                        <x-ui.icon
                            name="x"
                            size="15"
                        />

                        Limpiar
                    </a>

                @endif

            </div>

        </div>

    </form>


    {{-- TABLA --}}
    <div
        class="
            overflow-hidden
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-sm
        "
    >

        <div
            class="
                flex
                flex-col
                gap-2

                border-b
                border-slate-200

                px-5
                py-4

                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <div>

                <h2
                    class="
                        text-base
                        font-bold
                        text-slate-950
                    "
                >
                    Lotes registrados
                </h2>

                <p
                    class="
                        mt-0.5
                        text-sm
                        text-slate-500
                    "
                >
                    Seguimiento de compra y recepción física en Cochabamba.
                </p>

            </div>


            @if(filled($buscar) || filled($estado))

                <span
                    class="
                        inline-flex
                        w-fit
                        items-center

                        rounded-full
                        border
                        border-blue-200

                        bg-blue-50

                        px-3
                        py-1

                        text-xs
                        font-bold
                        text-blue-800
                    "
                >
                    Filtros activos
                </span>

            @endif

        </div>


        <div class="overflow-x-auto">

            <table
                class="
                    min-w-[920px]
                    w-full
                    divide-y
                    divide-slate-200
                "
            >

                <thead class="bg-slate-50">

                    <tr>

                        <th
                            scope="col"
                            class="
                                px-6
                                py-4
                                text-left
                                text-xs
                                font-bold
                                uppercase
                                tracking-wide
                                text-slate-500
                            "
                        >
                            Lote
                        </th>

                        <th
                            scope="col"
                            class="
                                px-6
                                py-4
                                text-left
                                text-xs
                                font-bold
                                uppercase
                                tracking-wide
                                text-slate-500
                            "
                        >
                            Proveedor
                        </th>

                        <th
                            scope="col"
                            class="
                                px-6
                                py-4
                                text-left
                                text-xs
                                font-bold
                                uppercase
                                tracking-wide
                                text-slate-500
                            "
                        >
                            Origen
                        </th>

                        <th
                            scope="col"
                            class="
                                px-6
                                py-4
                                text-left
                                text-xs
                                font-bold
                                uppercase
                                tracking-wide
                                text-slate-500
                            "
                        >
                            Recepción física
                        </th>

                        <th
                            scope="col"
                            class="
                                px-6
                                py-4
                                text-left
                                text-xs
                                font-bold
                                uppercase
                                tracking-wide
                                text-slate-500
                            "
                        >
                            Estado
                        </th>

                        <th
                            scope="col"
                            class="
                                px-6
                                py-4
                                text-right
                            "
                        >
                            <span class="sr-only">
                                Acciones
                            </span>
                        </th>

                    </tr>

                </thead>


                <tbody
                    class="
                        divide-y
                        divide-slate-100
                        bg-white
                    "
                >

                    @forelse($lotes as $lote)

                        @php
                            $esperadas =
                                (int) (
                                    $lote->cantidad_esperada_total
                                    ?? 0
                                );

                            /*
                             * Recepción física real en Cochabamba.
                             * No usamos detalles_lotes.cantidad_recibida,
                             * porque corresponde a la etapa posterior
                             * de recepción en Oruro.
                             */
                            $recibidas =
                                (int) (
                                    $lote->cantidad_recibida_fisica_total
                                    ?? 0
                                );

                            $porcentaje =
                                $esperadas > 0
                                    ? min(
                                        100,
                                        round(
                                            ($recibidas / $esperadas)
                                            * 100
                                        )
                                    )
                                    : 0;

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

                            $progressClass =
                                match ($lote->estado) {
                                    'RECIBIDO',
                                    'CERRADO'
                                        => 'bg-emerald-500',

                                    'RECEPCION_PARCIAL'
                                        => 'bg-amber-500',

                                    default
                                        => 'bg-oneshop-primary',
                                };
                        @endphp


                        <tr
                            class="
                                transition
                                duration-150
                                hover:bg-oneshop-soft
                            "
                        >

                            {{-- LOTE --}}
                            <td class="px-6 py-5">

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
                                            h-10
                                            w-10
                                            shrink-0
                                            items-center
                                            justify-center

                                            rounded-xl
                                            border
                                            border-blue-100

                                            bg-oneshop-light
                                            text-oneshop-primary
                                        "
                                    >
                                        <x-ui.icon
                                            name="package"
                                            size="19"
                                        />
                                    </div>


                                    <div class="min-w-0">

                                        <p
                                            class="
                                                font-bold
                                                text-slate-950
                                            "
                                        >
                                            {{ $lote->codigo }}
                                        </p>

                                        <p
                                            class="
                                                mt-0.5
                                                max-w-52
                                                truncate
                                                text-xs
                                                text-slate-500
                                            "
                                        >
                                            {{ $lote->referencia_compra ?: 'Sin referencia de compra' }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- PROVEEDOR --}}
                            <td
                                class="
                                    px-6
                                    py-5
                                    text-sm
                                    font-medium
                                    text-slate-700
                                "
                            >
                                {{ $lote->proveedor?->nombre ?? 'Sin proveedor' }}
                            </td>


                            {{-- ORIGEN --}}
                            <td
                                class="
                                    px-6
                                    py-5
                                    text-sm
                                    text-slate-600
                                "
                            >
                                {{ $lote->origen ?: 'No especificado' }}
                            </td>


                            {{-- RECEPCIÓN --}}
                            <td class="px-6 py-5">

                                <div class="min-w-40">

                                    <div
                                        class="
                                            flex
                                            items-center
                                            justify-between
                                            gap-4
                                            text-xs
                                        "
                                    >

                                        <span
                                            class="
                                                font-bold
                                                text-slate-700
                                            "
                                        >
                                            {{ $recibidas }}
                                            /
                                            {{ $esperadas }}
                                        </span>

                                        <span
                                            class="
                                                font-semibold
                                                text-slate-500
                                            "
                                        >
                                            {{ $porcentaje }}%
                                        </span>

                                    </div>


                                    <div
                                        class="
                                            mt-2
                                            h-2
                                            overflow-hidden
                                            rounded-full
                                            bg-slate-200
                                        "
                                    >

                                        <div
                                            class="
                                                h-full
                                                rounded-full
                                                transition-all
                                                duration-300

                                                {{ $progressClass }}
                                            "
                                            style="
                                                width:
                                                {{ $porcentaje }}%;
                                            "
                                        ></div>

                                    </div>

                                </div>

                            </td>


                            {{-- ESTADO --}}
                            <td class="px-6 py-5">

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

                            </td>


                            {{-- ACCIÓN --}}
                            <td
                                class="
                                    px-6
                                    py-5
                                    text-right
                                "
                            >

                                <a
                                    href="{{ route('importaciones.show', $lote) }}"
                                    class="
                                        inline-flex
                                        items-center
                                        justify-center
                                        gap-1.5

                                        rounded-lg
                                        border
                                        border-blue-200

                                        bg-blue-50

                                        px-3
                                        py-2

                                        text-sm
                                        font-bold
                                        text-oneshop-dark

                                        transition

                                        hover:bg-blue-100

                                        focus:outline-none
                                        focus:ring-4
                                        focus:ring-blue-100
                                    "
                                >
                                    <x-ui.icon
                                        name="eye"
                                        size="16"
                                    />

                                    Ver detalle
                                </a>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="
                                    px-6
                                    py-16
                                    text-center
                                "
                            >

                                <div
                                    class="
                                        mx-auto
                                        flex
                                        h-12
                                        w-12
                                        items-center
                                        justify-center

                                        rounded-xl
                                        bg-slate-100
                                        text-slate-500
                                    "
                                >
                                    <x-ui.icon
                                        name="package"
                                        size="22"
                                    />
                                </div>


                                <p
                                    class="
                                        mt-4
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    No existen lotes para mostrar
                                </p>


                                <p
                                    class="
                                        mx-auto
                                        mt-1
                                        max-w-md
                                        text-sm
                                        text-slate-500
                                    "
                                >
                                    No encontramos lotes que coincidan
                                    con los criterios actuales.
                                </p>


                                @if(
                                    auth()
                                        ->user()
                                        ->tienePermiso(
                                            'importacion.gestionar'
                                        )
                                    && !filled($buscar)
                                    && !filled($estado)
                                )

                                    <a
                                        href="{{ route('importaciones.create') }}"
                                        class="
                                            mt-5
                                            inline-flex
                                            items-center
                                            justify-center
                                            gap-2

                                            rounded-xl
                                            border
                                            border-oneshop-primary

                                            bg-oneshop-light

                                            px-4
                                            py-2.5

                                            text-sm
                                            font-bold
                                            text-oneshop-dark

                                            transition

                                            hover:bg-blue-100
                                        "
                                    >
                                        <x-ui.icon
                                            name="plus"
                                            size="16"
                                        />

                                        Crear primer lote
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($lotes->hasPages())

            <div
                class="
                    border-t
                    border-slate-200
                    bg-slate-50/50
                    px-6
                    py-4
                "
            >
                {{ $lotes->links() }}
            </div>

        @endif

    </div>

</div>

</x-layouts.oneshop>