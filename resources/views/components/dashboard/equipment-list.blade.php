@php
    $statusStyles = [
        'DISPONIBLE' => [
            'bg' => 'bg-emerald-50',
            'text' => 'text-emerald-800',
            'border' => 'border-emerald-200',
        ],

        'RESERVADO' => [
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-800',
            'border' => 'border-amber-200',
        ],

        'VENDIDO' => [
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-700',
            'border' => 'border-slate-200',
        ],

        'RECIBIDO' => [
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-800',
            'border' => 'border-blue-200',
        ],

        'PENDIENTE_REVISION' => [
            'bg' => 'bg-amber-50',
            'text' => 'text-amber-800',
            'border' => 'border-amber-200',
        ],

        'EN_DIAGNOSTICO' => [
            'bg' => 'bg-violet-50',
            'text' => 'text-violet-800',
            'border' => 'border-violet-200',
        ],

        'EN_REPARACION' => [
            'bg' => 'bg-orange-50',
            'text' => 'text-orange-800',
            'border' => 'border-orange-200',
        ],

        'PREPARACION' => [
            'bg' => 'bg-sky-50',
            'text' => 'text-sky-800',
            'border' => 'border-sky-200',
        ],

        'GARANTIA' => [
            'bg' => 'bg-rose-50',
            'text' => 'text-rose-800',
            'border' => 'border-rose-200',
        ],

        'DEVUELTO' => [
            'bg' => 'bg-red-50',
            'text' => 'text-red-800',
            'border' => 'border-red-200',
        ],

        'DADO_DE_BAJA' => [
            'bg' => 'bg-slate-100',
            'text' => 'text-slate-600',
            'border' => 'border-slate-300',
        ],
    ];
@endphp


<div
    class="
        h-full
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-6
        shadow-sm
    "
>

    <div
        class="
            mb-5
            flex
            items-start
            justify-between
            gap-4
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
                Inventario
            </p>

            <h3
                class="
                    mt-1
                    text-lg
                    font-bold
                    text-slate-950
                "
            >
                Últimos equipos registrados
            </h3>

            <p
                class="
                    mt-1
                    text-sm
                    leading-5
                    text-slate-500
                "
            >
                Incorporaciones recientes al inventario OneShop.
            </p>

        </div>


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
                size="20"
            />
        </div>

    </div>


    <div class="space-y-3">

        @forelse($equipos as $equipo)

            @php
                $codigoEstado =
                    $equipo->estadoActual?->codigo;

                $status =
                    $statusStyles[$codigoEstado]
                    ?? [
                        'bg' => 'bg-slate-100',
                        'text' => 'text-slate-700',
                        'border' => 'border-slate-200',
                    ];
            @endphp


            <div
                class="
                    group
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    p-4

                    transition
                    duration-200

                    hover:border-blue-200
                    hover:bg-oneshop-soft
                "
            >

                <div
                    class="
                        flex
                        flex-col
                        gap-4
                        sm:flex-row
                        sm:items-center
                        sm:justify-between
                    "
                >

                    <div
                        class="
                            flex
                            min-w-0
                            items-center
                            gap-3
                        "
                    >

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

                                bg-blue-50
                                text-oneshop-primary
                            "
                        >
                            <x-ui.icon
                                name="laptop"
                                size="21"
                            />
                        </div>


                        <div class="min-w-0">

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{ $equipo->producto->marca->nombre ?? 'Sin marca' }}

                                {{ $equipo->producto->modelo ?? 'Sin modelo' }}
                            </p>


                            <div
                                class="
                                    mt-1
                                    flex
                                    flex-wrap
                                    items-center
                                    gap-x-3
                                    gap-y-1
                                "
                            >

                                <span
                                    class="
                                        text-xs
                                        font-semibold
                                        text-slate-600
                                    "
                                >
                                    {{ $equipo->codigo_interno }}
                                </span>


                                @if($equipo->serial_fabricante)

                                    <span
                                        class="
                                            truncate
                                            text-xs
                                            text-slate-400
                                        "
                                    >
                                        Serial:
                                        {{ $equipo->serial_fabricante }}
                                    </span>

                                @endif

                            </div>

                        </div>

                    </div>


                    <div
                        class="
                            flex
                            shrink-0
                            items-center
                            justify-between
                            gap-3
                            sm:flex-col
                            sm:items-end
                        "
                    >

                        <span
                            class="
                                inline-flex
                                items-center

                                rounded-full
                                border

                                px-2.5
                                py-1

                                text-xs
                                font-bold

                                {{ $status['bg'] }}
                                {{ $status['text'] }}
                                {{ $status['border'] }}
                            "
                        >
                            {{ $equipo->estadoActual->nombre ?? 'Sin estado' }}
                        </span>


                        <span
                            class="
                                flex
                                items-center
                                gap-1.5
                                text-xs
                                font-medium
                                text-slate-400
                            "
                        >
                            <x-ui.icon
                                name="calendar"
                                size="14"
                            />

                            {{ optional($equipo->fecha_registro)->format('d/m/Y') }}
                        </span>

                    </div>

                </div>

            </div>


        @empty

            <div
                class="
                    rounded-xl
                    border
                    border-dashed
                    border-slate-300
                    bg-slate-50
                    px-5
                    py-10
                    text-center
                "
            >

                <div
                    class="
                        mx-auto
                        flex
                        h-11
                        w-11
                        items-center
                        justify-center

                        rounded-xl
                        bg-slate-100
                        text-slate-500
                    "
                >
                    <x-ui.icon
                        name="package"
                        size="21"
                    />
                </div>

                <p
                    class="
                        mt-3
                        text-sm
                        font-semibold
                        text-slate-700
                    "
                >
                    No hay equipos registrados
                </p>

                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-500
                    "
                >
                    Los equipos incorporados recientemente aparecerán aquí.
                </p>

            </div>

        @endforelse

    </div>

</div>