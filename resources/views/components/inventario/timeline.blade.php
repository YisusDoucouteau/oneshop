<section
    class="
    rounded-2xl
    border
    border-blue-100
    bg-white
    shadow-md
    "
>

    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-900">
    Línea de vida del equipo
</h2>

<p class="mt-1 text-sm text-slate-500">
    Trazabilidad completa del ciclo de vida del equipo.
</p>


    <div class="px-10 py-12">


        @if(count($eventos))


        @php

            $ultimo =
            collect($eventos)
            ->search(fn($evento)=>$evento['activo'] ?? false);

        @endphp



        <div class="relative">


            {{-- línea base --}}

            <div
                class="
                absolute
                left-10
                right-10
                top-8
                h-1
                rounded-full
                bg-slate-200
                "
            ></div>



            {{-- progreso --}}

            <div
                id="timeline-progress"

                class="
                absolute
                left-10
                top-8
                h-1
                rounded-full
                bg-blue-600
                transition-all
                duration-700
                "
            ></div>




            <div
                class="
                relative
                flex
                justify-between
                gap-8
                "
            >


            @foreach($eventos as $index=>$evento)


                @php

                    $activo =
                    $evento['activo'] ?? false;


                    $completado =
                    $index < $ultimo;

                @endphp



                <button

                    type="button"

                    class="
                    timeline-item
                    group
                    relative
                    min-w-[150px]
                    text-center
                    "

                    data-index="{{ $index }}"
                >



                    <div
                        class="
                        timeline-circle

                        relative

                        mx-auto

                        flex
                        h-16
                        w-16

                        items-center
                        justify-center

                        rounded-full

                        border-4
                        border-white

                        transition-all
                        duration-500
                        "

                        @class([

                            'bg-blue-600 text-white scale-110 ring-4 ring-blue-200 shadow-xl'
                            =>
                            $activo,


                            'bg-blue-50 text-blue-600 shadow-md'
                            =>
                            $completado,


                            'bg-white text-slate-400 shadow-sm'
                            =>
                            !$activo && !$completado,

                        ])

                    >


                        @if($activo)

                            <span
                                class="
                                absolute
                                inset-0
                                rounded-full
                                bg-blue-400/30
                                animate-ping
                                "
                            ></span>

                        @endif



                        <span class="relative z-10">


                            <x-ui.icon

                                name="{{ $evento['icono'] ?? 'package' }}"

                                size="26"

                            />


                        </span>



                    </div>





                    <p

                        class="
                        mt-4
                        text-sm
                        font-semibold
                        text-slate-700
                        "
                    >

                        {{ $evento['titulo'] }}

                    </p>



                    <p

                        class="
                        mt-1
                        text-xs
                        text-slate-400
                        "
                    >

                        {{
                            isset($evento['fecha'])

                            ?

                            \Carbon\Carbon::parse($evento['fecha'])
                            ->format('d/m/Y')

                            :

                            ''

                        }}

                    </p>



                </button>



            @endforeach


            </div>



        </div>





        {{-- detalle evento --}}


        <div

            id="timeline-detail"

            class="
            mt-12
            rounded-xl
            bg-slate-50
            p-8
            transition-all
            duration-300
            "

        >


            @php

    $primero =
    $eventos->last();

@endphp




            <h3

                class="
                font-semibold
                text-slate-900
                "

            >

                {{ $primero['titulo'] }}

            </h3>




            <p

                class="
                mt-3
                text-sm
                text-slate-600
                "

            >

                {{ $primero['detalle'] ?? '' }}

            </p>



            @if(!empty($primero['usuario']))


                <p

                    class="
                    mt-4
                    text-sm
                    text-slate-500
                    "

                >

                    Responsable:
                    {{ $primero['usuario'] }}

                </p>


            @endif



        </div>



        @else


        <div class="py-8 text-center">

            <p class="font-medium text-slate-800">
                Sin movimientos registrados
            </p>


            <p class="mt-1 text-sm text-slate-500">
                Este equipo todavía no tiene historial.
            </p>


        </div>


        @endif


    </div>


</section>





<script>

document.addEventListener(
'DOMContentLoaded',
()=>{


const eventos = @json($eventos);


const items =
document.querySelectorAll('.timeline-item');


const progress =
document.getElementById(
'timeline-progress'
);



const detail =
document.getElementById(
'timeline-detail'
);



function activar(index){



    items.forEach((item,i)=>{


        const circle =
        item.querySelector(
        '.timeline-circle'
        );


        if(i<=index){

            circle.classList.add(
                'bg-blue-600',
                'text-white',
                'shadow-xl'
            );


            circle.classList.remove(
                'bg-white',
                'text-slate-400'
            );


        }

    });




    let porcentaje = 0;


    if(eventos.length > 1){

        porcentaje =
        (index/(eventos.length-1))*100;

    }


    progress.style.width =
    porcentaje+"%";




    const evento =
    eventos[index];



    detail.classList.add(
        'opacity-0'
    );



    setTimeout(()=>{


        detail.innerHTML = `

        <h3 class="font-semibold text-slate-900">

            ${evento.titulo}

        </h3>


        <p class="mt-3 text-sm text-slate-600">

            ${evento.detalle ?? ''}

        </p>


        ${
            evento.usuario

            ?

            `
            <p class="mt-4 text-sm text-slate-500">

            Responsable:
            ${evento.usuario}

            </p>
            `

            :

            ''

        }

        `;


        detail.classList.remove(
            'opacity-0'
        );


    },200);



}





items.forEach(
(item,index)=>{


item.addEventListener(
'click',
()=>activar(index)
);


});



activar(
eventos.length-1
);



});

</script>