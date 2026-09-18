<section class="mb-6 rounded-2xl border border-slate-200 bg-white shadow-sm">


    <div class="border-b border-slate-200 px-6 py-5">

        <h2 class="font-semibold text-slate-950">
            Productos del lote
        </h2>

        <p class="text-sm text-slate-500">
            Productos esperados dentro de esta importación.
        </p>

    </div>




    <div class="p-6">



        <table class="w-full text-sm">



            <thead>

                <tr class="border-b text-left text-slate-500">


                    <th class="py-3">
                        Producto
                    </th>


                    <th>
                        Cantidad esperada
                    </th>


                    <th>
                        Cantidad recibida
                    </th>


                    <th>
                        Pendiente
                    </th>


                </tr>


            </thead>






            <tbody id="tablaDetalles">



            @forelse($lote->detalles as $detalle)



                @php

                    $recibidas = 
                        $detalle->unidadesAdquiridas
                        ->where(
                            'estado',
                            '!=',
                            'ANULADA'
                        )
                        ->count();



                    $pendientes =
                        $detalle->cantidad_esperada
                        -
                        $recibidas;


                @endphp





                <tr

                    class="border-b"

                    data-detalle-id="{{ $detalle->id }}"

                >



                    <td class="py-3 font-medium">


                        {{ $detalle->producto?->marca?->nombre }}

                        {{ $detalle->producto?->nombre }}

                        {{ $detalle->producto?->modelo }}


                    </td>





                    <td class="cantidad-esperada">


                        {{ $detalle->cantidad_esperada }}


                    </td>





                    <td class="cantidad-recibida">


                        {{ $recibidas }}


                    </td>





                    <td>


                        @if($pendientes > 0)


                            <span

                            class="rounded-full bg-yellow-100 px-3 py-1 text-xs text-yellow-700"

                            >

                                {{ $pendientes }}

                                pendiente(s)

                            </span>


                        @else


                            <span

                            class="rounded-full bg-green-100 px-3 py-1 text-xs text-green-700"

                            >

                                COMPLETO

                            </span>


                        @endif


                    </td>




                </tr>




            @empty



                <tr id="filaVacia">


                    <td

                    colspan="4"

                    class="py-6 text-center text-slate-500"

                    >

                        Todavía no existen productos registrados en este lote.


                    </td>


                </tr>



            @endforelse





            </tbody>



        </table>









        <hr class="my-6">






        <h3 class="mb-4 font-semibold">

            Agregar producto al lote

        </h3>






        <form id="formAgregarProducto">



            @csrf





            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">






                <select

                name="producto_id"

                id="selectProducto"

                class="rounded-xl border p-3"

                required

                >




                    <option value="">

                        Seleccione producto

                    </option>




                    <option value="crear">

                        + Crear producto nuevo

                    </option>





                    @foreach($productos as $producto)



                        <option value="{{ $producto->id }}">


                            {{ $producto->marca?->nombre }}

                            {{ $producto->nombre }}

                            {{ $producto->modelo }}



                        </option>



                    @endforeach





                </select>








                <input

                type="number"

                name="cantidad_esperada"

                value="1"

                min="1"

                class="rounded-xl border p-3"

                required

                >

                <input
                    type="number"
                    name="costo_unitario_origen"
                    min="0"
                    step="0.01"
                    placeholder="Costo unitario"
                    class="rounded-xl border p-3"
                >

                <select
                    name="moneda_id"
                    id="monedaDetalleLote"
                    class="rounded-xl border p-3"
                >
                    <option value="">Moneda del costo</option>
                    @foreach($monedas as $moneda)
                        <option value="{{ $moneda->id }}" data-codigo="{{ $moneda->codigo }}">
                            {{ $moneda->codigo }}
                        </option>
                    @endforeach
                </select>

                <div id="grupoTipoCambioDetalle" class="hidden">
                    <input
                        type="number"
                        name="tipo_cambio_aplicado"
                        id="tipoCambioDetalleLote"
                        min="0.0001"
                        step="0.0001"
                        placeholder="Tipo de cambio aplicado"
                        class="w-full rounded-xl border p-3"
                    >
                    <button
                        type="button"
                        id="usarReferenciaDetalle"
                        class="mt-2 hidden text-xs font-semibold text-blue-700 hover:text-blue-900"
                    >
                        Usar referencia USD/BOB
                    </button>
                </div>

                <button
                    type="submit"
                    class="rounded-xl bg-slate-950 px-5 py-3 font-semibold text-white hover:bg-slate-800"
                >
                    Agregar producto
                </button>

            </div>

            <details class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <summary class="cursor-pointer font-semibold text-slate-800">
                    Características y accesorios esperados (opcional)
                </summary>

                <p class="mt-2 text-sm text-slate-500">
                    Registra lo informado por el proveedor; se comprobará físicamente al recibir los equipos.
                </p>

                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <input name="especificacion_esperada[procesador]" placeholder="Procesador" class="rounded-xl border p-3">
                    <input name="especificacion_esperada[generacion_procesador]" placeholder="Generación" class="rounded-xl border p-3">
                    <input type="number" min="0" name="especificacion_esperada[ram_gb]" placeholder="RAM (GB)" class="rounded-xl border p-3">
                    <input type="number" min="0" name="especificacion_esperada[almacenamiento_gb]" placeholder="Almacenamiento (GB)" class="rounded-xl border p-3">
                    <select name="especificacion_esperada[tipo_almacenamiento]" class="rounded-xl border p-3">
                        <option value="">Tipo de almacenamiento</option>
                        <option value="SSD">SSD</option>
                        <option value="HDD">HDD</option>
                        <option value="NVME">NVMe</option>
                        <option value="EMMC">eMMC</option>
                    </select>
                    <input name="especificacion_esperada[tarjeta_grafica]" placeholder="Tarjeta gráfica" class="rounded-xl border p-3">
                    <input type="number" min="0" step="0.1" name="especificacion_esperada[pantalla_pulgadas]" placeholder="Pantalla (pulgadas)" class="rounded-xl border p-3">
                    <input name="especificacion_esperada[resolucion]" placeholder="Resolución" class="rounded-xl border p-3">
                    <input name="especificacion_esperada[sistema_operativo]" placeholder="Sistema operativo" class="rounded-xl border p-3">
                </div>

                <div class="mt-5 border-t border-slate-200 pt-4">
                    <div class="flex items-center justify-between gap-3">
                        <h4 class="font-semibold text-slate-800">Accesorios por equipo</h4>
                        <button type="button" id="agregarComponenteEsperado" class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                            + Agregar accesorio
                        </button>
                    </div>
                    <div id="componentesEsperados" class="mt-3 space-y-3"></div>
                </div>
            </details>





        </form>






    </div>





</section>








<script>


// Crear producto nuevo

document

.getElementById('selectProducto')

.addEventListener(

'change',

function(){



    if(this.value === 'crear'){


        window.abrirModalProducto();


        this.value = "";


    }


});









const monedaDetalle = document.getElementById('monedaDetalleLote');
const grupoTipoCambioDetalle = document.getElementById('grupoTipoCambioDetalle');
const tipoCambioDetalle = document.getElementById('tipoCambioDetalleLote');
const usarReferenciaDetalle = document.getElementById('usarReferenciaDetalle');

function actualizarTipoCambioDetalle() {
    const codigo = monedaDetalle.options[monedaDetalle.selectedIndex]?.dataset.codigo;
    const requiereTipoCambio = codigo === 'USD' || codigo === 'USDT';

    grupoTipoCambioDetalle.classList.toggle('hidden', !requiereTipoCambio);
    tipoCambioDetalle.required = requiereTipoCambio;
    usarReferenciaDetalle.classList.toggle('hidden', codigo !== 'USD');

    if (!requiereTipoCambio) {
        tipoCambioDetalle.value = '';
    }
}

monedaDetalle.addEventListener('change', actualizarTipoCambioDetalle);

usarReferenciaDetalle.addEventListener('click', function () {
    @if($referenciaUsdBob)
        tipoCambioDetalle.value = @js((string) $referenciaUsdBob['tipo_cambio']->valor);
        tipoCambioDetalle.focus();
    @endif
});

let indiceComponenteEsperado = 0;

document.getElementById('agregarComponenteEsperado').addEventListener('click', function () {
    const indice = indiceComponenteEsperado++;
    const fila = document.createElement('div');

    fila.className = 'grid gap-3 rounded-xl border border-slate-200 bg-white p-3 md:grid-cols-[2fr_1fr_2fr_auto]';
    fila.innerHTML = `
        <input name="componentes_esperados[${indice}][nombre]" placeholder="Ej. cargador" class="rounded-xl border p-3" required>
        <input type="number" min="1" value="1" name="componentes_esperados[${indice}][cantidad_por_unidad]" class="rounded-xl border p-3" required>
        <input name="componentes_esperados[${indice}][observacion]" placeholder="Observación opcional" class="rounded-xl border p-3">
        <button type="button" class="quitar-componente rounded-xl px-3 text-sm font-semibold text-red-700 hover:bg-red-50">Quitar</button>
        <input type="hidden" name="componentes_esperados[${indice}][incluido_en_compra]" value="1">
    `;

    fila.querySelector('.quitar-componente').addEventListener('click', () => fila.remove());
    document.getElementById('componentesEsperados').appendChild(fila);
    fila.querySelector('input').focus();
});

// Agregar producto lote

document

.getElementById('formAgregarProducto')

.addEventListener(

'submit',

async function(e){



    e.preventDefault();




    let formulario = this;



    let datos = new FormData(formulario);







    let respuesta = await fetch(


        "{{ route('importaciones.detalles.store',$lote) }}",


        {



            method:"POST",


            headers:{


                "X-CSRF-TOKEN":

                document

                .querySelector(

                'meta[name="csrf-token"]'

                )

                .content,



                "Accept":"application/json"



            },



            body:datos



        }



    );







    let json = await respuesta.json();






    if(json.ok){



        let detalle = json.detalle;





        let filaExistente =

        document.querySelector(

            `[data-detalle-id="${detalle.id}"]`

        );







        if(filaExistente){



            filaExistente

            .querySelector(

                '.cantidad-esperada'

            )

            .innerText =

            detalle.cantidad_esperada;




            filaExistente

            .querySelector(

                '.cantidad-recibida'

            )

            .innerText =

            detalle.cantidad_recibida;



        }

        else{



            let fila = `



            <tr

            class="border-b"

            data-detalle-id="${detalle.id}"

            >



                <td class="py-3 font-medium">

                    ${detalle.producto}

                </td>




                <td class="cantidad-esperada">

                    ${detalle.cantidad_esperada}

                </td>





                <td class="cantidad-recibida">

                    ${detalle.cantidad_recibida}

                </td>





                <td>


                    <span

                    class="rounded-full bg-yellow-100 px-3 py-1 text-xs text-yellow-700"

                    >

                        Pendiente

                    </span>


                </td>



            </tr>



            `;






            let vacia =

            document.getElementById(

                'filaVacia'

            );





            if(vacia){

                vacia.remove();

            }






            document

            .getElementById(

                'tablaDetalles'

            )

            .insertAdjacentHTML(

                'beforeend',

                fila

            );



        }






        formulario.reset();

        document.getElementById('componentesEsperados').innerHTML = '';
        actualizarTipoCambioDetalle();




    }

    else{


        alert(

            json.message ??

            "Error registrando producto"

        );


    }





});




</script>
