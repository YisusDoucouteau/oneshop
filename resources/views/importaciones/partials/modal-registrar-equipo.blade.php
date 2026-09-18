<div
    id="modalRegistrarEquipo"
    class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm"
>

    <div class="flex min-h-screen items-center justify-center p-4">

        <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">


            {{-- HEADER --}}
            <div class="flex items-center justify-between bg-slate-950 px-6 py-4">

                <h2 class="flex items-center gap-2 font-semibold text-white">

                    <i
                        data-lucide="monitor"
                        class="h-5 w-5"
                    ></i>

                    Registrar equipo recibido

                </h2>


                <button
                    type="button"
                    onclick="cerrarModalEquipo()"
                    class="text-xl text-white transition hover:text-slate-300"
                >
                    ×
                </button>

            </div>



            {{-- INDICADORES DE PASOS --}}
            <div class="flex justify-center gap-10 border-b border-slate-200 px-6 py-5">


                {{-- PASO 1 --}}
                <div
                    id="paso-indicador-1"
                    class="flex items-center gap-2 font-semibold"
                >

                    <span
                        class="numero-paso rounded-full bg-slate-950 px-3 py-1 text-white"
                    >
                        1
                    </span>

                    <i
                        data-lucide="monitor"
                        class="h-4 w-4"
                    ></i>

                    Equipo

                </div>



                {{-- PASO 2 --}}
                <div
                    id="paso-indicador-2"
                    class="flex items-center gap-2 text-slate-400"
                >

                    <span
                        class="numero-paso rounded-full border px-3 py-1"
                    >
                        2
                    </span>

                    <i
                        data-lucide="wallet"
                        class="h-4 w-4"
                    ></i>

                    Compra

                </div>



                {{-- PASO 3 --}}
                <div
                    id="paso-indicador-3"
                    class="flex items-center gap-2 text-slate-400"
                >

                    <span
                        class="numero-paso rounded-full border px-3 py-1"
                    >
                        3
                    </span>

                    <i
                        data-lucide="settings"
                        class="h-4 w-4"
                    ></i>

                    Detalles

                </div>


            </div>



            {{-- FORMULARIO --}}
            <form
                method="POST"
                action="{{ route('importaciones.unidades.store', $lote) }}"
                onsubmit="return confirmarRegistro()"
                class="max-h-[70vh] overflow-y-auto p-6"
            >

                @csrf

                <input
                    type="hidden"
                    name="cantidad"
                    value="1"
                >



                {{-- ERRORES DEVUELTOS POR LARAVEL --}}
                @if($errors->any())

                    <div
                        class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                    >

                        <div class="flex items-center gap-2 font-semibold">

                            <i
                                data-lucide="circle-alert"
                                class="h-5 w-5"
                            ></i>

                            Revise los siguientes datos:

                        </div>


                        <ul class="mt-2 list-disc pl-5">

                            @foreach($errors->all() as $error)

                                <li>
                                    {{ $error }}
                                </li>

                            @endforeach

                        </ul>

                    </div>

                @endif



                {{-- ERROR DE VALIDACIÓN DEL WIZARD --}}
                <div
                    id="errorPaso"
                    class="mb-5 hidden rounded-xl border border-red-200 bg-red-50 p-4"
                >

                    <div class="flex items-start gap-3">

                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-100"
                        >

                            <i
                                data-lucide="triangle-alert"
                                class="h-5 w-5 text-red-600"
                            ></i>

                        </div>


                        <div>

                            <p class="font-semibold text-red-700">
                                Complete la información requerida
                            </p>

                            <p
                                id="mensajePaso"
                                class="mt-1 text-sm text-red-600"
                            ></p>

                        </div>

                    </div>

                </div>



                {{-- ===================================================== --}}
                {{-- PASO 1: EQUIPO --}}
                {{-- ===================================================== --}}

                <div id="paso1">


                    <h3 class="mb-5 flex items-center gap-2 text-lg font-semibold">

                        <i
                            data-lucide="laptop"
                            class="h-5 w-5 text-slate-600"
                        ></i>

                        Información del equipo

                    </h3>



                    {{-- PRODUCTO --}}
                    <div>

                        <label class="text-sm font-medium text-slate-700">
                            Producto del lote *
                        </label>


                        <select
                            name="detalle_lote_id"
                            class="mt-2 w-full rounded-xl border border-slate-300 p-3"
                        >

                            <option value="">
                                Seleccione producto
                            </option>


                            @foreach($lote->detalles as $detalle)

                                @php

                                    $unidadesActivas =
                                        $detalle
                                        ->unidadesAdquiridas
                                        ->where(
                                            'estado',
                                            '!=',
                                            \App\Models\UnidadAdquirida::ESTADO_ANULADA
                                        )
                                        ->count();


                                    $disponibles =
                                        $detalle->cantidad_esperada
                                        -
                                        $unidadesActivas;

                                @endphp


                                @if($disponibles > 0)

                                    <option
                                        value="{{ $detalle->id }}"
                                        {{ old('detalle_lote_id') == $detalle->id ? 'selected' : '' }}
                                    >

                                        {{ $detalle->producto?->marca?->nombre }}

                                        {{ $detalle->producto?->nombre }}

                                        {{ $detalle->producto?->modelo }}

                                        (Disponible: {{ $disponibles }})

                                    </option>

                                @endif

                            @endforeach

                        </select>

                    </div>



                    {{-- PROCESADOR / GENERACIÓN / RAM / DISCO --}}
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">


                        <input
                            name="procesador"
                            value="{{ old('procesador') }}"
                            placeholder="Procesador Ej: Core i5"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            name="generacion_procesador"
                            value="{{ old('generacion_procesador') }}"
                            placeholder="Generación Ej: 10th"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            type="number"
                            min="1"
                            name="ram_gb"
                            value="{{ old('ram_gb') }}"
                            placeholder="RAM GB"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            type="number"
                            min="1"
                            name="almacenamiento_gb"
                            value="{{ old('almacenamiento_gb') }}"
                            placeholder="Disco GB"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                    </div>



                    {{-- TIPO DISCO / GPU --}}
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">


                        <select
                            name="tipo_almacenamiento"
                            class="rounded-xl border border-slate-300 p-3"
                        >

                            <option value="">
                                Tipo disco
                            </option>

                            <option
                                value="SSD"
                                {{ old('tipo_almacenamiento') === 'SSD' ? 'selected' : '' }}
                            >
                                SSD
                            </option>

                            <option
                                value="NVME"
                                {{ old('tipo_almacenamiento') === 'NVME' ? 'selected' : '' }}
                            >
                                NVMe
                            </option>

                            <option
                                value="HDD"
                                {{ old('tipo_almacenamiento') === 'HDD' ? 'selected' : '' }}
                            >
                                HDD
                            </option>

                        </select>


                        <input
                            name="tarjeta_grafica"
                            value="{{ old('tarjeta_grafica') }}"
                            placeholder="GPU"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                    </div>



                    {{-- SERIAL / CARGADOR --}}
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">


                        <input
                            name="serial_fabricante"
                            value="{{ old('serial_fabricante') }}"
                            placeholder="Serial fabricante"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <select
                            name="tiene_cargador"
                            class="rounded-xl border border-slate-300 p-3"
                        >

                            <option value="">
                                Seleccione cargador
                            </option>

                            <option
                                value="1"
                                {{ old('tiene_cargador') === '1' ? 'selected' : '' }}
                            >
                                Sí tiene cargador
                            </option>

                            <option
                                value="0"
                                {{ old('tiene_cargador') === '0' ? 'selected' : '' }}
                            >
                                No tiene cargador
                            </option>

                        </select>


                    </div>


                </div>



                {{-- ===================================================== --}}
                {{-- PASO 2: COMPRA --}}
                {{-- ===================================================== --}}

                <div
                    id="paso2"
                    class="hidden"
                >


                    <h3 class="mb-5 flex items-center gap-2 text-lg font-semibold">

                        <i
                            data-lucide="wallet"
                            class="h-5 w-5 text-slate-600"
                        ></i>

                        Información de compra

                    </h3>



                    {{-- PRECIO / MONEDA --}}
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">


                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            name="precio_compra"
                            value="{{ old('precio_compra') }}"
                            placeholder="Precio compra"
                            class="rounded-xl border border-slate-300 p-3"
                        >



                        <select
                            id="moneda_compra_equipo"
                            name="moneda_id"
                            class="rounded-xl border border-slate-300 p-3"
                        >

                            <option value="">
                                Moneda
                            </option>


                            @foreach(
                                \App\Models\Moneda::where('activo', true)
                                ->orderBy('codigo')
                                ->get()
                                as $moneda
                            )

                                <option
                                    value="{{ $moneda->id }}"
                                    {{ old('moneda_id') == $moneda->id ? 'selected' : '' }}
                                >
                                    {{ $moneda->codigo }}
                                </option>

                            @endforeach

                        </select>


                    </div>



                    {{-- TIPO CAMBIO --}}
                    <input
                        id="tipo_cambio_compra_equipo"
                        type="number"
                        min="0"
                        step="0.000001"
                        name="tipo_cambio_compra"
                        value="{{ old('tipo_cambio_compra') }}"
                        placeholder="Tipo cambio"
                        class="mt-5 w-full rounded-xl border border-slate-300 p-3"
                    >

                    @if($referenciaUsdBob)
                        @php
                            $tipoCambioReferencia = $referenciaUsdBob['tipo_cambio'];
                        @endphp
                        <div class="mt-3 flex flex-col gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-blue-950">
                                    Referencia USD/BOB: {{ number_format((float) $tipoCambioReferencia->valor, 4) }}
                                </p>
                                <p class="mt-1 text-xs text-blue-700">
                                    Fuente: {{ $referenciaUsdBob['origen'] === 'API' ? 'API BCBO' : 'último valor guardado' }}
                                    · {{ $tipoCambioReferencia->fecha_vigencia?->format('d/m/Y') }}
                                    @if($referenciaUsdBob['desactualizado'])
                                        · Puede estar desactualizado
                                    @endif
                                </p>
                            </div>
                            <button
                                type="button"
                                onclick="usarReferenciaUsdBob()"
                                class="shrink-0 rounded-lg bg-blue-700 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800"
                            >
                                Usar referencia
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Es una ayuda para USD. Confirma siempre el tipo de cambio realmente pagado; USDT se registra manualmente.
                        </p>
                    @else
                        <p class="mt-2 text-xs text-amber-700">
                            No fue posible obtener la referencia USD/BOB. Ingresa el tipo de cambio aplicado manualmente.
                        </p>
                    @endif



                    {{-- FECHA / PROVEEDOR --}}
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">


                        <input
                            type="date"
                            name="fecha_compra"
                            value="{{ old('fecha_compra', date('Y-m-d')) }}"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            name="proveedor_compra"
                            value="{{ old('proveedor_compra') }}"
                            placeholder="Proveedor"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                    </div>



                    {{-- REFERENCIA --}}
                    <input
                        name="referencia_compra"
                        value="{{ old('referencia_compra') }}"
                        placeholder="Factura o referencia"
                        class="mt-5 w-full rounded-xl border border-slate-300 p-3"
                    >


                </div>



                {{-- ===================================================== --}}
                {{-- PASO 3: DETALLES --}}
                {{-- ===================================================== --}}

                <div
                    id="paso3"
                    class="hidden"
                >


                    <h3 class="mb-5 flex items-center gap-2 text-lg font-semibold">

                        <i
                            data-lucide="settings"
                            class="h-5 w-5 text-slate-600"
                        ></i>

                        Detalles adicionales

                    </h3>



                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">


                        <input
                            name="sistema_operativo"
                            value="{{ old('sistema_operativo') }}"
                            placeholder="Sistema operativo"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            name="resolucion"
                            value="{{ old('resolucion') }}"
                            placeholder="Resolución"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            type="number"
                            min="0"
                            step="0.1"
                            name="pantalla_pulgadas"
                            value="{{ old('pantalla_pulgadas') }}"
                            placeholder="Pulgadas"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            name="servicio_requerido"
                            value="{{ old('servicio_requerido') }}"
                            placeholder="Servicio requerido"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                    </div>



                    <textarea
                        name="observacion"
                        rows="3"
                        placeholder="Observación recepción"
                        class="mt-5 w-full resize-none rounded-xl border border-slate-300 p-3"
                    >{{ old('observacion') }}</textarea>


                </div>



                {{-- ===================================================== --}}
                {{-- BOTONES --}}
                {{-- ===================================================== --}}

                <div class="mt-8 flex justify-between border-t border-slate-200 pt-5">


                    <button
                        type="button"
                        onclick="cerrarModalEquipo()"
                        class="rounded-xl border border-slate-300 px-5 py-2 text-sm font-medium transition hover:bg-slate-50"
                    >
                        Cancelar
                    </button>



                    <div class="flex gap-3">


                        <button
                            id="btnAnterior"
                            type="button"
                            onclick="cambiarPaso(-1)"
                            class="hidden rounded-xl border border-slate-300 px-5 py-2 text-sm font-medium transition hover:bg-slate-50"
                        >
                            Atrás
                        </button>



                        <button
                            id="btnSiguiente"
                            type="button"
                            onclick="cambiarPaso(1)"
                            class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                        >

                            Continuar

                            <i
                                data-lucide="arrow-right"
                                class="h-4 w-4"
                            ></i>

                        </button>



                        <button
                            id="btnGuardar"
                            type="submit"
                            class="hidden inline-flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-green-700"
                        >

                            <i
                                data-lucide="save"
                                class="h-4 w-4"
                            ></i>

                            Guardar equipo

                        </button>


                    </div>


                </div>


            </form>


        </div>

    </div>

</div>



@push('scripts')

<script>

let pasoActual = 1;


/*
|--------------------------------------------------------------------------
| ABRIR MODAL
|--------------------------------------------------------------------------
*/

function abrirModalEquipo()
{
    const modal =
        document.getElementById('modalRegistrarEquipo');

    if (!modal) {
        console.error('No existe modalRegistrarEquipo');
        return;
    }

    modal.classList.remove('hidden');

    pasoActual = 1;

    limpiarErrorPaso();

    limpiarCamposError();

    mostrarPaso();
}



/*
|--------------------------------------------------------------------------
| CERRAR MODAL
|--------------------------------------------------------------------------
*/

function cerrarModalEquipo()
{
    const modal =
        document.getElementById('modalRegistrarEquipo');

    if (!modal) {
        return;
    }

    modal.classList.add('hidden');

    limpiarErrorPaso();

    limpiarCamposError();
}



/*
|--------------------------------------------------------------------------
| CAMBIAR PASO
|--------------------------------------------------------------------------
*/

function cambiarPaso(valor)
{
    if (valor > 0) {

        const valido =
            validarPasoActual();


        if (!valido) {
            return;
        }
    }


    const nuevoPaso =
        pasoActual + valor;


    if (
        nuevoPaso < 1 ||
        nuevoPaso > 3
    ) {
        return;
    }


    limpiarErrorPaso();
    limpiarCamposError();


    document
        .getElementById('paso' + pasoActual)
        .classList
        .add('hidden');


    document
        .getElementById('paso' + nuevoPaso)
        .classList
        .remove('hidden');


    pasoActual =
        nuevoPaso;


    actualizarBotones();
}



/*
|--------------------------------------------------------------------------
| VALIDACIÓN POR PASO
|--------------------------------------------------------------------------
*/

function validarPasoActual()
{
    limpiarErrorPaso();
    limpiarCamposError();


    // Trabajamos únicamente dentro del modal
    const modal =
        document.getElementById('modalRegistrarEquipo');


    if (!modal) {
        console.error('No existe modalRegistrarEquipo');
        return false;
    }



    /*
    |--------------------------------------------------------------------------
    | PASO 1 - EQUIPO
    |--------------------------------------------------------------------------
    */

    if (pasoActual === 1) {


        const producto =
            modal.querySelector(
                '[name="detalle_lote_id"]'
            );


        const procesador =
            modal.querySelector(
                '[name="procesador"]'
            );


        const cargador =
            modal.querySelector(
                '[name="tiene_cargador"]'
            );



        // PRODUCTO
        if (
            !producto ||
            producto.value === ''
        ) {

            mostrarErrorPaso(
                'Debe seleccionar el producto del lote.',
                producto
            );

            return false;
        }



        // PROCESADOR
        if (
            !procesador ||
            procesador.value.trim() === ''
        ) {

            mostrarErrorPaso(
                'Debe ingresar el procesador del equipo.',
                procesador
            );

            return false;
        }



        // CARGADOR
        if (
            !cargador ||
            cargador.value === ''
        ) {

            mostrarErrorPaso(
                'Debe indicar si el equipo tiene cargador.',
                cargador
            );

            return false;
        }

    }





    /*
    |--------------------------------------------------------------------------
    | PASO 2 - COMPRA
    |--------------------------------------------------------------------------
    */

    if (pasoActual === 2) {


        const precio =
            modal.querySelector(
                '[name="precio_compra"]'
            );


        const moneda =
            modal.querySelector(
                '[name="moneda_id"]'
            );



        // PRECIO
        if (
            !precio ||
            precio.value.trim() === '' ||
            Number(precio.value) <= 0
        ) {

            mostrarErrorPaso(
                'Debe ingresar un precio de compra válido.',
                precio
            );

            return false;
        }



        // MONEDA
        if (
            !moneda ||
            moneda.value === ''
        ) {

            mostrarErrorPaso(
                'Debe seleccionar la moneda de compra.',
                moneda
            );

            return false;
        }

    }





    /*
    |--------------------------------------------------------------------------
    | PASO 3 - DETALLES
    |--------------------------------------------------------------------------
    |
    | Sin campos obligatorios.
    |
    */


    return true;
}



/*
|--------------------------------------------------------------------------
| MOSTRAR ERROR DENTRO DEL MODAL
|--------------------------------------------------------------------------
*/

function mostrarErrorPaso(
    mensaje,
    campo = null
)
{
    const caja =
        document.getElementById('errorPaso');


    const texto =
        document.getElementById('mensajePaso');


    if (!caja || !texto) {
        return;
    }


    texto.textContent =
        mensaje;


    caja.classList.remove('hidden');


    /*
     * Marcar visualmente el campo.
     */
    if (campo) {

        campo.classList.add(
            'border-red-400',
            'ring-2',
            'ring-red-100'
        );


        /*
         * Lleva el cursor al campo faltante.
         */
        try {
            campo.focus();
        } catch (error) {
            // No hacemos nada.
        }
    }


    /*
     * Como el formulario tiene scroll interno,
     * llevamos el mensaje a la zona visible.
     */
    caja.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest'
    });
}



/*
|--------------------------------------------------------------------------
| LIMPIAR MENSAJE
|--------------------------------------------------------------------------
*/

function limpiarErrorPaso()
{
    const caja =
        document.getElementById('errorPaso');


    const texto =
        document.getElementById('mensajePaso');


    if (caja) {
        caja.classList.add('hidden');
    }


    if (texto) {
        texto.textContent = '';
    }
}



/*
|--------------------------------------------------------------------------
| LIMPIAR BORDES ROJOS
|--------------------------------------------------------------------------
*/

function limpiarCamposError()
{
    const modal =
        document.getElementById('modalRegistrarEquipo');


    if (!modal) {
        return;
    }


    const campos =
        modal.querySelectorAll(
            'input, select, textarea'
        );


    campos.forEach(campo => {

        campo.classList.remove(
            'border-red-400',
            'ring-2',
            'ring-red-100'
        );

    });
}



/*
|--------------------------------------------------------------------------
| MOSTRAR PASO ACTUAL
|--------------------------------------------------------------------------
*/

function mostrarPaso()
{
    [1, 2, 3].forEach(p => {

        const paso =
            document.getElementById(
                'paso' + p
            );


        if (paso) {

            paso.classList.add(
                'hidden'
            );

        }

    });


    const paso =
        document.getElementById(
            'paso' + pasoActual
        );


    if (paso) {

        paso.classList.remove(
            'hidden'
        );

    }


    actualizarBotones();
}



/*
|--------------------------------------------------------------------------
| BOTONES E INDICADORES
|--------------------------------------------------------------------------
*/

function actualizarBotones()
{
    const btnAnterior =
        document.getElementById(
            'btnAnterior'
        );


    const btnSiguiente =
        document.getElementById(
            'btnSiguiente'
        );


    const btnGuardar =
        document.getElementById(
            'btnGuardar'
        );



    if (btnAnterior) {

        btnAnterior
            .classList
            .toggle(
                'hidden',
                pasoActual === 1
            );

    }



    if (btnSiguiente) {

        btnSiguiente
            .classList
            .toggle(
                'hidden',
                pasoActual === 3
            );

    }



    if (btnGuardar) {

        btnGuardar
            .classList
            .toggle(
                'hidden',
                pasoActual !== 3
            );

    }



    /*
    |--------------------------------------------------------------------------
    | INDICADORES 1 - 2 - 3
    |--------------------------------------------------------------------------
    */

    for (
        let i = 1;
        i <= 3;
        i++
    ) {

        const indicador =
            document.getElementById(
                'paso-indicador-' + i
            );


        if (!indicador) {
            continue;
        }


        const numero =
            indicador.querySelector(
                '.numero-paso'
            );


        if (!numero) {
            continue;
        }



        /*
         * Limpiamos estado anterior.
         */

        indicador.classList.remove(
            'text-slate-400',
            'text-green-700',
            'font-semibold'
        );


        numero.classList.remove(
            'bg-slate-950',
            'bg-green-100',
            'text-white',
            'text-green-700',
            'border',
            'border-slate-200'
        );



        /*
         * ACTIVO
         */
        if (i === pasoActual) {

            indicador.classList.add(
                'font-semibold'
            );


            numero.classList.add(
                'bg-slate-950',
                'text-white'
            );

        }


        /*
         * COMPLETADO
         */
        else if (i < pasoActual) {

            indicador.classList.add(
                'font-semibold',
                'text-green-700'
            );


            numero.classList.add(
                'bg-green-100',
                'text-green-700'
            );

        }


        /*
         * PENDIENTE
         */
        else {

            indicador.classList.add(
                'text-slate-400'
            );


            numero.classList.add(
                'border',
                'border-slate-200'
            );

        }

    }
}



/*
|--------------------------------------------------------------------------
| TIPO DE CAMBIO DE REFERENCIA
|--------------------------------------------------------------------------
*/

function usarReferenciaUsdBob()
{
    const moneda = document.getElementById(
        'moneda_compra_equipo'
    );

    const tipoCambio = document.getElementById(
        'tipo_cambio_compra_equipo'
    );

    const codigoMoneda = moneda
        ?.options[moneda.selectedIndex]
        ?.textContent
        ?.trim();

    if (codigoMoneda !== 'USD') {
        alert(
            'La referencia automática corresponde únicamente a USD/BOB. Selecciona USD o registra manualmente el valor aplicado para USDT.'
        );

        return;
    }

    @if($referenciaUsdBob)
        tipoCambio.value = @js((string) $referenciaUsdBob['tipo_cambio']->valor);
        tipoCambio.focus();
    @endif
}


/*
|--------------------------------------------------------------------------
| CONFIRMAR REGISTRO
|--------------------------------------------------------------------------
*/

function confirmarRegistro()
{
    /*
     * Como el usuario solo puede llegar al paso 3
     * después de superar las validaciones de 1 y 2,
     * aquí simplemente confirmamos.
     */

    return confirm(
        '¿Confirmar registro de este equipo recibido?'
    );
}

</script>

@endpush
