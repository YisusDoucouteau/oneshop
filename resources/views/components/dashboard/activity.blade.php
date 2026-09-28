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
                Trazabilidad
            </p>

            <h3
                class="
                    mt-1
                    text-lg
                    font-bold
                    text-slate-950
                "
            >
                Actividad reciente
            </h3>

            <p
                class="
                    mt-1
                    text-sm
                    leading-5
                    text-slate-500
                "
            >
                Últimos cambios de estado registrados.
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
                name="history"
                size="20"
            />
        </div>

    </div>


    <div>

        @forelse($movimientos as $movimiento)

            <div
                class="
                    group
                    relative
                    flex
                    gap-4
                    border-b
                    border-slate-100
                    py-4

                    first:pt-0
                    last:border-b-0
                    last:pb-0
                "
            >

                <div
                    class="
                        mt-0.5
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center

                        rounded-xl
                        border
                        border-blue-100

                        bg-blue-50
                        text-oneshop-primary

                        transition
                        duration-200

                        group-hover:bg-oneshop-light
                    "
                >
                    <x-ui.icon
                        name="history"
                        size="18"
                    />
                </div>


                <div class="min-w-0 flex-1">

                    <div
                        class="
                            flex
                            flex-col
                            gap-1
                            sm:flex-row
                            sm:items-start
                            sm:justify-between
                            sm:gap-4
                        "
                    >

                        <div class="min-w-0">

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{ $movimiento->equipo->producto->marca->nombre ?? 'Equipo' }}

                                {{ $movimiento->equipo->producto->modelo ?? '' }}
                            </p>


                            <p
                                class="
                                    mt-0.5
                                    text-xs
                                    font-semibold
                                    text-slate-500
                                "
                            >
                                {{ $movimiento->equipo->codigo_interno ?? 'Sin código' }}
                            </p>

                        </div>


                        <span
                            class="
                                shrink-0
                                text-xs
                                font-medium
                                text-slate-400
                            "
                        >
                            {{ optional($movimiento->fecha_cambio)->format('d/m/Y H:i') }}
                        </span>

                    </div>


                    @if(
                        $movimiento->estadoOrigen
                        && $movimiento->estadoDestino
                    )

                        <div
                            class="
                                mt-3
                                flex
                                flex-wrap
                                items-center
                                gap-2
                            "
                        >

                            <span
                                class="
                                    rounded-lg
                                    border
                                    border-slate-200
                                    bg-slate-50
                                    px-2
                                    py-1
                                    text-xs
                                    font-semibold
                                    text-slate-600
                                "
                            >
                                {{ $movimiento->estadoOrigen->nombre }}
                            </span>


                            <span
                                class="
                                    text-xs
                                    font-bold
                                    text-oneshop-primary
                                "
                                aria-hidden="true"
                            >
                                →
                            </span>


                            <span
                                class="
                                    rounded-lg
                                    border
                                    border-blue-200
                                    bg-oneshop-light
                                    px-2
                                    py-1
                                    text-xs
                                    font-bold
                                    text-oneshop-dark
                                "
                            >
                                {{ $movimiento->estadoDestino->nombre }}
                            </span>

                        </div>

                    @else

                        <p
                            class="
                                mt-2
                                text-sm
                                text-slate-600
                            "
                        >
                            Movimiento registrado.
                        </p>

                    @endif


                    @if($movimiento->usuario)

                        <p
                            class="
                                mt-2
                                text-xs
                                text-slate-400
                            "
                        >
                            Registrado por
                            <span class="font-semibold text-slate-600">
                                {{ $movimiento->usuario->name }}
                            </span>
                        </p>

                    @endif

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
                        name="history"
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
                    Sin actividad reciente
                </p>

                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-500
                    "
                >
                    Los cambios de estado aparecerán aquí.
                </p>

            </div>

        @endforelse

    </div>

</div>