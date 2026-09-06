<div
    class="
    mb-8
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
        flex
        flex-col
        gap-6
        lg:flex-row
        lg:items-start
        lg:justify-between
        "
    >



        {{-- Información principal --}}

        <div class="space-y-5">



            <div
                class="
                flex
                flex-wrap
                items-center
                gap-3
                "
            >


                <span
                    class="
                    inline-flex
                    items-center
                    rounded-xl
                    bg-blue-50
                    px-3
                    py-1.5
                    text-xs
                    font-bold
                    uppercase
                    tracking-wider
                    text-blue-700
                    "
                >

                    {{ $equipo->codigo_interno }}

                </span>




                <span
                    class="
                    inline-flex
                    items-center
                    rounded-full
                    bg-emerald-50
                    px-3
                    py-1.5
                    text-xs
                    font-semibold
                    text-emerald-700
                    "
                >

                    {{ $equipo->estadoActual?->nombre ?? 'Sin estado' }}

                </span>


            </div>






            <div>


                <h1
                    class="
                    text-3xl
                    font-bold
                    tracking-tight
                    text-slate-950
                    "
                >

                    {{ $equipo->producto?->nombre ?? 'Equipo sin producto' }}

                </h1>




                <p
                    class="
                    mt-2
                    text-base
                    text-slate-500
                    "
                >


                    {{ $equipo->producto?->marca?->nombre }}


                    @if($equipo->producto?->modelo)

                        <span class="mx-1 text-slate-300">
                            ·
                        </span>

                        {{ $equipo->producto->modelo }}

                    @endif


                </p>


            </div>







            <div
                class="
                grid
                gap-3
                sm:grid-cols-2
                "
            >



                @if($equipo->serial_fabricante)


                <div
                    class="
                    rounded-xl
                    bg-slate-50
                    px-4
                    py-3
                    "
                >

                    <p
                        class="
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wide
                        text-slate-400
                        "
                    >

                        Serial fabricante

                    </p>


                    <p
                        class="
                        mt-1
                        font-medium
                        text-slate-800
                        "
                    >

                        {{ $equipo->serial_fabricante }}

                    </p>


                </div>


                @endif






                @if($equipo->almacenActual)


                <div
                    class="
                    rounded-xl
                    bg-slate-50
                    px-4
                    py-3
                    "
                >

                    <p
                        class="
                        text-xs
                        font-semibold
                        uppercase
                        tracking-wide
                        text-slate-400
                        "
                    >

                        Ubicación actual

                    </p>



                    <p
                        class="
                        mt-1
                        font-medium
                        text-slate-800
                        "
                    >

                        {{ $equipo->almacenActual->nombre }}

                    </p>


                </div>


                @endif



            </div>




        </div>








        {{-- Acciones --}}


        <div
            class="
            flex
            gap-3
            "
        >



            @if(auth()->user()->tienePermiso('inventario.modificar'))


                <button
                    type="button"

                    class="
                    inline-flex
                    items-center
                    justify-center
                    rounded-xl
                    border
                    border-slate-300
                    bg-white
                    px-5
                    py-2.5
                    text-sm
                    font-semibold
                    text-slate-700
                    transition
                    hover:bg-slate-50
                    "
                >

                    Editar equipo

                </button>


            @endif






            <button
                type="button"

                class="
                inline-flex
                items-center
                justify-center
                rounded-xl
                bg-blue-600
                px-5
                py-2.5
                text-sm
                font-semibold
                text-white
                shadow-sm
                transition
                hover:bg-blue-700
                "
            >

                Etiqueta

            </button>



        </div>




    </div>


</div>