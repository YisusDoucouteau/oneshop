<div class="mb-8 grid gap-5 sm:grid-cols-2 xl:grid-cols-5">


    {{-- Ubicación --}}

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

        <div class="flex items-center gap-3">

            <div
                class="
                flex
                h-10
                w-10
                items-center
                justify-center
                rounded-xl
                bg-blue-50
                text-blue-600
                "
            >

                <x-ui.icon
                    name="home"
                    size="22"
                />

            </div>


            <p
                class="
                text-xs
                font-semibold
                uppercase
                tracking-wide
                text-slate-400
                "
            >

                Ubicación

            </p>


        </div>


        <p
            class="
            mt-5
            font-bold
            text-slate-900
            "
        >

            {{ $equipo->almacenActual?->nombre ?? 'Sin almacén' }}

        </p>



        @if($equipo->almacenActual?->ciudad)

            <p class="mt-1 text-sm text-slate-500">

                {{ $equipo->almacenActual->ciudad }}

            </p>

        @endif


    </div>






    {{-- Estado actual --}}

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


        <div class="flex items-center gap-3">


            <div
                class="
                flex
                h-10
                w-10
                items-center
                justify-center
                rounded-xl
                bg-emerald-50
                text-emerald-600
                "
            >

                <x-ui.icon
                    name="package"
                    size="22"
                />

            </div>


            <p
                class="
                text-xs
                font-semibold
                uppercase
                tracking-wide
                text-slate-400
                "
            >

                Estado

            </p>


        </div>



        <p
            class="
            mt-5
            font-bold
            text-slate-900
            "
        >

            {{ $equipo->estadoActual?->nombre ?? 'Sin estado' }}

        </p>



    </div>







    {{-- Condición física --}}


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


        <div class="flex items-center gap-3">


            <div
                class="
                flex
                h-10
                w-10
                items-center
                justify-center
                rounded-xl
                bg-violet-50
                text-violet-600
                "
            >

                <x-ui.icon
                    name="shield"
                    size="22"
                />

            </div>



            <p
                class="
                text-xs
                font-semibold
                uppercase
                tracking-wide
                text-slate-400
                "
            >

                Condición

            </p>


        </div>




        <p
            class="
            mt-5
            font-bold
            text-slate-900
            "
        >

            {{ $equipo->condicionFisica?->nombre ?? 'Pendiente' }}

        </p>



    </div>







    {{-- Precio --}}


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


        <div class="flex items-center gap-3">


            <div
                class="
                flex
                h-10
                w-10
                items-center
                justify-center
                rounded-xl
                bg-amber-50
                text-amber-600
                "
            >

                <x-ui.icon
                    name="chart"
                    size="22"
                />

            </div>


            <p
                class="
                text-xs
                font-semibold
                uppercase
                tracking-wide
                text-slate-400
                "
            >

                Precio

            </p>


        </div>




        @if($equipo->precioVigente)


            <p
                class="
                mt-5
                font-bold
                text-slate-900
                "
            >

                Bs {{ number_format(
                    (float)$equipo->precioVigente->precio_publico,
                    2
                ) }}


            </p>


        @else


            <p class="mt-5 font-bold text-slate-400">

                Sin precio

            </p>


        @endif



    </div>







    {{-- Registro --}}


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


        <div class="flex items-center gap-3">


            <div
                class="
                flex
                h-10
                w-10
                items-center
                justify-center
                rounded-xl
                bg-slate-100
                text-slate-600
                "
            >

                <x-ui.icon
                    name="chart"
                    size="22"
                />

            </div>


            <p
                class="
                text-xs
                font-semibold
                uppercase
                tracking-wide
                text-slate-400
                "
            >

                Registro

            </p>


        </div>



        <p
            class="
            mt-5
            font-bold
            text-slate-900
            "
        >

            {{ $equipo->fecha_registro?->format('d/m/Y') ?? '—' }}

        </p>


        @if($equipo->fecha_registro)

            <p class="mt-1 text-sm text-slate-500">

                {{ $equipo->fecha_registro->format('H:i') }}

            </p>

        @endif



    </div>




</div>