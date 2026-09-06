<section
    class="
    rounded-2xl
    border
    border-slate-200
    bg-white
    shadow-sm
    "
>


    <div class="border-b border-slate-200 px-6 py-5">


        <h2 class="font-semibold text-slate-950">

            Etiqueta del equipo

        </h2>


        <p class="mt-1 text-sm text-slate-500">

            Identificación física para control interno y trazabilidad.

        </p>


    </div>





    <div class="flex justify-center p-8">



        {{-- Etiqueta física --}}


        <div
            class="
            w-full
            max-w-sm
            rounded-3xl
            border-2
            border-dashed
            border-slate-300
            bg-white
            p-7
            text-center
            shadow-lg
            "
        >



            {{-- Marca --}}


            <div
                class="
                border-b
                border-slate-200
                pb-5
                "
            >


                <p
                    class="
                    text-xl
                    font-black
                    tracking-[0.35em]
                    text-slate-900
                    "
                >

                    ONESHOP

                </p>


                <p
                    class="
                    mt-2
                    text-xs
                    uppercase
                    tracking-wider
                    text-slate-400
                    "
                >

                    Control de activos

                </p>


            </div>







            {{-- Equipo --}}


            <div class="mt-6">


                <p
                    class="
                    text-lg
                    font-bold
                    text-slate-900
                    "
                >

                    {{ $equipo->producto?->marca?->nombre }}

                    {{ $equipo->producto?->modelo }}

                </p>



                <p
                    class="
                    mt-1
                    text-sm
                    text-slate-500
                    "
                >

                    {{ $equipo->producto?->nombre }}

                </p>



            </div>








            {{-- Código principal --}}


            <div
                class="
                mt-6
                rounded-2xl
                bg-slate-900
                p-5
                text-white
                "
            >


                <p
                    class="
                    text-xs
                    uppercase
                    tracking-widest
                    text-slate-300
                    "
                >

                    Código interno

                </p>



                <p
                    class="
                    mt-2
                    text-2xl
                    font-black
                    tracking-widest
                    "
                >

                    {{ $equipo->codigo_interno }}

                </p>



            </div>








            {{-- Datos secundarios --}}


            <div
                class="
                mt-6
                space-y-3
                "
            >



                <div
                    class="
                    rounded-xl
                    bg-slate-50
                    p-3
                    "
                >


                    <p
                        class="
                        text-xs
                        uppercase
                        tracking-wide
                        text-slate-400
                        "
                    >

                        Serial

                    </p>


                    <p class="mt-1 font-semibold text-slate-800">

                        {{ $equipo->serial_fabricante ?? 'Sin registro' }}

                    </p>


                </div>






                <div
                    class="
                    rounded-xl
                    bg-slate-50
                    p-3
                    "
                >


                    <p
                        class="
                        text-xs
                        uppercase
                        tracking-wide
                        text-slate-400
                        "
                    >

                        Estado

                    </p>



                    <p class="mt-1 font-semibold text-slate-800">

                        {{ $equipo->estadoActual?->nombre ?? 'Sin estado' }}

                    </p>



                </div>






                @if($equipo->almacenActual)


                <div
                    class="
                    rounded-xl
                    bg-slate-50
                    p-3
                    "
                >


                    <p
                        class="
                        text-xs
                        uppercase
                        tracking-wide
                        text-slate-400
                        "
                    >

                        Ubicación

                    </p>



                    <p class="mt-1 font-semibold text-slate-800">

                        {{ $equipo->almacenActual->nombre }}

                    </p>


                </div>


                @endif




            </div>








            {{-- QR preparado --}}


            <div
                class="
                mt-7
                flex
                justify-center
                "
            >


                <div
                    class="
                    flex
                    h-32
                    w-32
                    items-center
                    justify-center
                    rounded-2xl
                    border
                    border-slate-200
                    bg-slate-50
                    "
                >


                    <div class="text-center">


                        <x-ui.icon
                            name="package"
                            size="38"
                        />


                        <p
                            class="
                            mt-2
                            text-xs
                            text-slate-400
                            "
                        >

                            QR

                        </p>


                    </div>


                </div>



            </div>







            <p
                class="
                mt-6
                text-xs
                text-slate-400
                "
            >

                Escaneo para consulta de activo

            </p>



        </div>



    </div>



</section>