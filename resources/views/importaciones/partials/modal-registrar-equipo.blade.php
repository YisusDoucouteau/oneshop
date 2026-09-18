<div
    id="modalRegistrarEquipo"
    class="fixed inset-0 z-50 {{ old('_form_context') === 'recepcion_unidad' ? '' : 'hidden' }} bg-black/60 backdrop-blur-sm"
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

                    Origen

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

                <input type="hidden" name="_form_context" value="recepcion_unidad">

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
                            id="detalleLoteRecepcion"
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

                                    $esperada = $detalle->especificacionEsperada;

                                    $datosRecepcion = [
                                        'campos' => [
                                            'procesador' => $esperada?->procesador,
                                            'generacion_procesador' => $esperada?->generacion_procesador,
                                            'ram_gb' => $esperada?->ram_gb,
                                            'almacenamiento_gb' => $esperada?->almacenamiento_gb,
                                            'tipo_almacenamiento' => $esperada?->tipo_almacenamiento,
                                            'tarjeta_grafica' => $esperada?->tarjeta_grafica,
                                            'sistema_operativo' => $esperada?->sistema_operativo,
                                            'resolucion' => $esperada?->resolucion,
                                            'pantalla_pulgadas' => $esperada?->pantalla_pulgadas,
                                        ],
                                        'compra' => [
                                            'precio' => $detalle->costo_unitario_origen,
                                            'moneda' => $detalle->moneda?->codigo,
                                            'tipo_cambio' => $detalle->tipoCambioCompra?->valor,
                                            'costo_bob' => $detalle->costo_unitario_bob,
                                            'fecha' => $lote->fecha_compra?->format('d/m/Y'),
                                            'referencia' => $lote->referencia_compra,
                                            'proveedor' => $lote->proveedor?->nombre,
                                        ],
                                    ];

                                @endphp


                                @if($disponibles > 0)

                                    <option
                                        value="{{ $detalle->id }}"
                                        data-recepcion="{{ json_encode($datosRecepcion) }}"
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

                        <p class="mt-2 text-xs text-slate-500">
                            Al elegir el producto se cargarán como referencia los datos registrados en la compra. Confirma y corrige lo observado físicamente.
                        </p>

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
                            min="0"
                            name="ram_gb"
                            value="{{ old('ram_gb') }}"
                            placeholder="RAM GB"
                            class="rounded-xl border border-slate-300 p-3"
                        >


                        <input
                            type="number"
                            min="0"
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

                            <option
                                value="EMMC"
                                {{ old('tipo_almacenamiento') === 'EMMC' ? 'selected' : '' }}
                            >
                                eMMC
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

                        <select
                            name="grado_recibido"
                            class="rounded-xl border border-slate-300 p-3"
                            required
                        >
                            <option value="">Grado recibido</option>
                            <option value="A" {{ old('grado_recibido') === 'A' ? 'selected' : '' }}>
                                Grado A (90–100%)
                            </option>
                            <option value="B" {{ old('grado_recibido') === 'B' ? 'selected' : '' }}>
                                Grado B (70–90%)
                            </option>
                            <option value="C" {{ old('grado_recibido') === 'C' ? 'selected' : '' }}>
                                Grado C (50–70%)
                            </option>
                        </select>


                    </div>


                </div>



                {{-- ===================================================== --}}
                {{-- PASO 2: ORIGEN DE COMPRA --}}
                {{-- ===================================================== --}}

                <div
                    id="paso2"
                    class="hidden"
                >

                    <h3 class="mb-2 flex items-center gap-2 text-lg font-semibold">
                        <i
                            data-lucide="wallet"
                            class="h-5 w-5 text-slate-600"
                        ></i>

                        Origen de compra
                    </h3>

                    <p class="mb-5 text-sm text-slate-500">
                        Información heredada del lote. En recepción no se modifica el precio, la moneda ni el tipo de cambio.
                    </p>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Precio de compra</p>
                            <p id="compraPrecioRecepcion" class="mt-1 text-base font-semibold text-slate-900">—</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Costo convertido</p>
                            <p id="compraCostoBobRecepcion" class="mt-1 text-base font-semibold text-slate-900">—</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de cambio aplicado</p>
                            <p id="compraTipoCambioRecepcion" class="mt-1 text-base font-semibold text-slate-900">—</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fecha de compra</p>
                            <p id="compraFechaRecepcion" class="mt-1 text-base font-semibold text-slate-900">—</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Proveedor</p>
                            <p id="compraProveedorRecepcion" class="mt-1 text-base font-semibold text-slate-900">—</p>
                        </div>

                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Referencia</p>
                            <p id="compraReferenciaRecepcion" class="mt-1 text-base font-semibold text-slate-900">—</p>
                        </div>
                    </div>

                    <div id="compraIncompletaRecepcion" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Esta línea no tiene todos los datos económicos de compra. Puedes registrar la recepción, pero conviene completar la compra antes de cerrar el lote.
                    </div>

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

const detalleLoteRecepcion = document.getElementById('detalleLoteRecepcion');
const recepcionTieneOldInput = @js(old('_form_context') === 'recepcion_unidad');

function formatearMontoRecepcion(valor, moneda = '')
{
    if (valor === null || valor === undefined || valor === '') {
        return '—';
    }

    const numero = Number(valor);

    if (Number.isNaN(numero)) {
        return `${moneda ? moneda + ' ' : ''}${valor}`;
    }

    return `${moneda ? moneda + ' ' : ''}${numero.toLocaleString('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    })}`;
}

function actualizarResumenCompraRecepcion(compra = {})
{
    const asignar = (id, valor) => {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent = valor || '—';
        }
    };

    asignar(
        'compraPrecioRecepcion',
        formatearMontoRecepcion(compra.precio, compra.moneda || '')
    );

    asignar(
        'compraCostoBobRecepcion',
        formatearMontoRecepcion(compra.costo_bob, 'BOB')
    );

    asignar(
        'compraTipoCambioRecepcion',
        compra.tipo_cambio ? Number(compra.tipo_cambio).toFixed(6) : (compra.moneda === 'BOB' ? 'No aplica' : '—')
    );

    asignar('compraFechaRecepcion', compra.fecha || '—');
    asignar('compraProveedorRecepcion', compra.proveedor || '—');
    asignar('compraReferenciaRecepcion', compra.referencia || '—');

    const advertencia = document.getElementById('compraIncompletaRecepcion');
    const compraIncompleta = compra.precio === null
        || compra.precio === undefined
        || compra.precio === ''
        || !compra.moneda
        || !compra.fecha;

    advertencia?.classList.toggle('hidden', !compraIncompleta);
}

function cargarReferenciaRecepcion(rellenarCampos = true)
{
    if (!detalleLoteRecepcion) {
        return;
    }

    const opcion = detalleLoteRecepcion.options[detalleLoteRecepcion.selectedIndex];
    const formulario = detalleLoteRecepcion.closest('form');
    let datos = {};

    try {
        datos = opcion?.dataset.recepcion
            ? JSON.parse(opcion.dataset.recepcion)
            : {};
    } catch (error) {
        console.error('No fue posible leer los datos de referencia de recepción.', error);
    }

    if (rellenarCampos) {
        Object.entries(datos.campos || {}).forEach(([nombre, valor]) => {
            const campo = formulario?.elements.namedItem(nombre);

            if (campo) {
                campo.value = valor ?? '';
            }
        });
    }

    actualizarResumenCompraRecepcion(datos.compra || {});
}

detalleLoteRecepcion?.addEventListener('change', () => cargarReferenciaRecepcion(true));

if (detalleLoteRecepcion?.value) {
    cargarReferenciaRecepcion(!recepcionTieneOldInput);
}


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

        const grado =
            modal.querySelector(
                '[name="grado_recibido"]'
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


        // GRADO DE LLEGADA
        if (
            !grado ||
            grado.value === ''
        ) {

            mostrarErrorPaso(
                'Debe indicar el grado en el que llegó el equipo.',
                grado
            );

            return false;
        }

    }





    /*
    |--------------------------------------------------------------------------
    | PASO 2 - ORIGEN DE COMPRA
    |--------------------------------------------------------------------------
    |
    | Solo informativo. Los datos económicos pertenecen a la compra original
    | y no se editan durante la recepción física.
    |--------------------------------------------------------------------------
    */





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
