@php
    $totalTiposProducto = $lote->detalles->count();

    $totalEsperadas = $lote->detalles->sum(
        fn ($detalle) => (int) $detalle->cantidad_esperada
    );

    $totalRecibidas = $lote->detalles->sum(
        fn ($detalle) => $detalle->unidadesAdquiridas
            ->where('estado', '!=', 'ANULADA')
            ->count()
    );

    $totalPendientes = max(
        0,
        $totalEsperadas - $totalRecibidas
    );
@endphp


<section
    class="
        overflow-hidden
        rounded-2xl
        border
        border-blue-100
        bg-white
        shadow-sm
    "
>

    {{-- CABECERA --}}
    <div
        class="
            flex
            flex-col
            gap-4
            border-b
            border-blue-100
            bg-gradient-to-r
            from-blue-50
            via-oneshop-soft
            to-white
            px-6
            py-5
            lg:flex-row
            lg:items-center
            lg:justify-between
        "
    >

        <div class="flex items-start gap-3">

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
                    bg-white
                    text-oneshop-primary
                    shadow-sm
                "
            >
                <x-ui.icon
                    name="package"
                    size="20"
                />
            </div>

            <div>

                <p
                    class="
                        text-xs
                        font-bold
                        uppercase
                        tracking-[0.14em]
                        text-oneshop-primary
                    "
                >
                    Composición
                </p>

                <h2
                    class="
                        mt-0.5
                        text-lg
                        font-bold
                        text-slate-950
                    "
                >
                    Productos del lote
                </h2>

                <p
                    class="
                        mt-1
                        text-sm
                        text-slate-500
                    "
                >
                    Productos, cantidades y características esperadas en la compra.
                </p>

            </div>

        </div>


        {{-- RESUMEN RÁPIDO --}}
        <div
            class="
                grid
                grid-cols-3
                gap-2
            "
        >

            <div
                class="
                    min-w-24
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-3
                    py-2
                    text-center
                "
            >
                <p
                    class="
                        text-lg
                        font-bold
                        text-slate-950
                    "
                >
                    {{ $totalEsperadas }}
                </p>

                <p class="text-[11px] font-semibold text-slate-500">
                    Esperadas
                </p>
            </div>


            <div
                class="
                    min-w-24
                    rounded-xl
                    border
                    border-emerald-200
                    bg-emerald-50
                    px-3
                    py-2
                    text-center
                "
            >
                <p
                    class="
                        text-lg
                        font-bold
                        text-emerald-800
                    "
                >
                    {{ $totalRecibidas }}
                </p>

                <p class="text-[11px] font-semibold text-emerald-700">
                    Recibidas
                </p>
            </div>


            <div
                class="
                    min-w-24
                    rounded-xl
                    border
                    border-amber-200
                    bg-amber-50
                    px-3
                    py-2
                    text-center
                "
            >
                <p
                    class="
                        text-lg
                        font-bold
                        text-amber-800
                    "
                >
                    {{ $totalPendientes }}
                </p>

                <p class="text-[11px] font-semibold text-amber-700">
                    Pendientes
                </p>
            </div>

        </div>

    </div>


    {{-- TABLA DE COMPOSICIÓN --}}
    <div class="p-6">

        <div
            class="
                mb-4
                flex
                flex-col
                gap-2
                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <div>

                <h3
                    class="
                        text-sm
                        font-bold
                        text-slate-900
                    "
                >
                    Composición registrada
                </h3>

                <p
                    class="
                        mt-0.5
                        text-xs
                        text-slate-500
                    "
                >
                    {{ $totalTiposProducto }}
                    {{ $totalTiposProducto === 1 ? 'tipo de producto' : 'tipos de producto' }}
                    en este lote.
                </p>

            </div>

        </div>


        <div
            class="
                overflow-hidden
                rounded-xl
                border
                border-slate-200
            "
        >

            <div class="overflow-x-auto">

                <table
                    class="
                        min-w-[760px]
                        w-full
                        divide-y
                        divide-slate-200
                        text-sm
                    "
                >

                    <thead class="bg-slate-50">

                        <tr>

                            <th
                                class="
                                    px-5
                                    py-3.5
                                    text-left
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Producto
                            </th>

                            <th
                                class="
                                    px-5
                                    py-3.5
                                    text-center
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Esperada
                            </th>

                            <th
                                class="
                                    px-5
                                    py-3.5
                                    text-center
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Recibida
                            </th>

                            <th
                                class="
                                    px-5
                                    py-3.5
                                    text-right
                                    text-xs
                                    font-bold
                                    uppercase
                                    tracking-wide
                                    text-slate-500
                                "
                            >
                                Estado
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        id="tablaDetalles"
                        class="
                            divide-y
                            divide-slate-100
                            bg-white
                        "
                    >

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
                                    max(
                                        0,
                                        (int) $detalle->cantidad_esperada
                                        - $recibidas
                                    );
                            @endphp


                            <tr
                                data-detalle-id="{{ $detalle->id }}"
                                class="
                                    transition
                                    duration-150
                                    hover:bg-oneshop-soft
                                "
                            >

                                {{-- PRODUCTO --}}
                                <td class="px-5 py-4">

                                    <div class="flex items-center gap-3">

                                        <div
                                            class="
                                                flex
                                                h-9
                                                w-9
                                                shrink-0
                                                items-center
                                                justify-center
                                                rounded-lg
                                                bg-blue-50
                                                text-oneshop-primary
                                            "
                                        >
                                            <x-ui.icon
                                                name="package"
                                                size="17"
                                            />
                                        </div>

                                        <div class="min-w-0">

                                            <p
                                                class="
                                                    font-bold
                                                    text-slate-900
                                                "
                                            >
                                                {{ $detalle->producto?->marca?->nombre }}

                                                {{ $detalle->producto?->nombre }}

                                                {{ $detalle->producto?->modelo }}
                                            </p>

                                        </div>

                                    </div>

                                </td>


                                {{-- ESPERADA --}}
                                <td
                                    class="
                                        cantidad-esperada
                                        px-5
                                        py-4
                                        text-center
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    {{ $detalle->cantidad_esperada }}
                                </td>


                                {{-- RECIBIDA --}}
                                <td
                                    class="
                                        cantidad-recibida
                                        px-5
                                        py-4
                                        text-center
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    {{ $recibidas }}
                                </td>


                                {{-- ESTADO --}}
                                <td
                                    class="
                                        estado-pendiente
                                        px-5
                                        py-4
                                        text-right
                                    "
                                >

                                    @if($pendientes > 0)

                                        <span
                                            class="
                                                inline-flex
                                                items-center
                                                rounded-full
                                                border
                                                border-amber-200
                                                bg-amber-50
                                                px-3
                                                py-1
                                                text-xs
                                                font-bold
                                                text-amber-800
                                            "
                                        >
                                            {{ $pendientes }}
                                            pendiente{{ $pendientes === 1 ? '' : 's' }}
                                        </span>

                                    @else

                                        <span
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1.5
                                                rounded-full
                                                border
                                                border-emerald-200
                                                bg-emerald-50
                                                px-3
                                                py-1
                                                text-xs
                                                font-bold
                                                text-emerald-800
                                            "
                                        >
                                            <x-ui.icon
                                                name="check"
                                                size="12"
                                            />

                                            Completo
                                        </span>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr id="filaVacia">

                                <td
                                    colspan="4"
                                    class="
                                        px-6
                                        py-12
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
                                            size="20"
                                        />
                                    </div>

                                    <p
                                        class="
                                            mt-3
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Sin productos registrados
                                    </p>

                                    <p
                                        class="
                                            mt-1
                                            text-sm
                                            text-slate-500
                                        "
                                    >
                                        Agrega la composición esperada del lote.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


    {{-- AGREGAR PRODUCTO --}}
    <div
        class="
            border-t
            border-blue-100
            bg-blue-50/25
            px-6
            py-6
        "
    >

        <div
            class="
                mb-5
                flex
                items-start
                gap-3
            "
        >

            <div
                class="
                    flex
                    h-9
                    w-9
                    shrink-0
                    items-center
                    justify-center
                    rounded-lg
                    border
                    border-blue-100
                    bg-white
                    text-oneshop-primary
                "
            >
                <x-ui.icon
                    name="plus"
                    size="17"
                />
            </div>

            <div>

                <h3
                    class="
                        text-sm
                        font-bold
                        text-slate-900
                    "
                >
                    Agregar producto al lote
                </h3>

                <p
                    class="
                        mt-0.5
                        text-xs
                        text-slate-500
                    "
                >
                    Define qué producto se espera recibir y su costo de compra.
                </p>

            </div>

        </div>


        <form
            id="formAgregarProducto"
            class="space-y-5"
        >
            @csrf


            {{-- DATOS PRINCIPALES --}}
            <div
                class="
                    grid
                    gap-4
                    md:grid-cols-2
                    xl:grid-cols-12
                "
            >

                {{-- PRODUCTO --}}
                <div class="xl:col-span-4">

                    <label
                        for="selectProducto"
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
                        Producto
                    </label>

                    <select
                        name="producto_id"
                        id="selectProducto"
                        required
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

                </div>


                {{-- CANTIDAD --}}
                <div class="xl:col-span-2">

                    <label
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
                        Cantidad
                    </label>

                    <input
                        type="number"
                        name="cantidad_esperada"
                        value="1"
                        min="1"
                        required
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

                </div>


                {{-- COSTO --}}
                <div class="xl:col-span-2">

                    <label
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
                        Costo unitario
                    </label>

                    <input
                        type="number"
                        name="costo_unitario_origen"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
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
                            placeholder:text-slate-400
                            focus:border-oneshop-primary
                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100
                        "
                    >

                </div>


                {{-- MONEDA --}}
                <div class="xl:col-span-2">

                    <label
                        for="monedaDetalleLote"
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
                        Moneda
                    </label>

                    <select
                        name="moneda_id"
                        id="monedaDetalleLote"
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
                            Seleccionar
                        </option>

                        @foreach($monedas as $moneda)

                            <option
                                value="{{ $moneda->id }}"
                                data-codigo="{{ $moneda->codigo }}"
                            >
                                {{ $moneda->codigo }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- TIPO DE CAMBIO --}}
                <div
                    id="grupoTipoCambioDetalle"
                    class="
                        hidden
                        xl:col-span-2
                    "
                >

                    <label
                        for="tipoCambioDetalleLote"
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
                        Tipo de cambio
                    </label>

                    <input
                        type="number"
                        name="tipo_cambio_aplicado"
                        id="tipoCambioDetalleLote"
                        min="0.0001"
                        step="0.0001"
                        placeholder="0.0000"
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
                            placeholder:text-slate-400
                            focus:border-oneshop-primary
                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100
                        "
                    >

                    <button
                        type="button"
                        id="usarReferenciaDetalle"
                        class="
                            mt-2
                            hidden
                            text-xs
                            font-bold
                            text-oneshop-primary
                            hover:text-oneshop-dark
                        "
                    >
                        Usar referencia USD/BOB
                    </button>

                </div>

            </div>


            {{-- CARACTERÍSTICAS OPCIONALES --}}
            <details
                class="
                    group
                    overflow-hidden
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                "
            >

                <summary
                    class="
                        flex
                        cursor-pointer
                        list-none
                        items-center
                        justify-between
                        gap-4
                        px-4
                        py-3.5
                        font-semibold
                        text-slate-800
                        transition
                        hover:bg-slate-50
                    "
                >

                    <div>

                        <p class="text-sm font-bold">
                            Características y accesorios esperados
                        </p>

                        <p
                            class="
                                mt-0.5
                                text-xs
                                font-normal
                                text-slate-500
                            "
                        >
                            Opcional · información declarada por el proveedor.
                        </p>

                    </div>

                    <span
                        class="
                            text-lg
                            font-bold
                            text-oneshop-primary
                            transition-transform
                            group-open:rotate-45
                        "
                    >
                        +
                    </span>

                </summary>


                <div
                    class="
                        border-t
                        border-slate-200
                        p-4
                    "
                >

                    <p
                        class="
                            mb-4
                            text-sm
                            leading-6
                            text-slate-500
                        "
                    >
                        Estos datos se utilizarán como referencia y se
                        comprobarán físicamente durante la recepción.
                    </p>


                    <div
                        class="
                            grid
                            gap-4
                            md:grid-cols-2
                            xl:grid-cols-4
                        "
                    >

                        <input
                            name="especificacion_esperada[procesador]"
                            placeholder="Procesador"
                            class="input-oneshop"
                        >

                        <input
                            name="especificacion_esperada[generacion_procesador]"
                            placeholder="Generación"
                            class="input-oneshop"
                        >

                        <input
                            type="number"
                            min="0"
                            name="especificacion_esperada[ram_gb]"
                            placeholder="RAM (GB)"
                            class="input-oneshop"
                        >

                        <input
                            type="number"
                            min="0"
                            name="especificacion_esperada[almacenamiento_gb]"
                            placeholder="Almacenamiento (GB)"
                            class="input-oneshop"
                        >

                        <select
                            name="especificacion_esperada[tipo_almacenamiento]"
                            class="input-oneshop"
                        >
                            <option value="">
                                Tipo de almacenamiento
                            </option>

                            <option value="SSD">
                                SSD
                            </option>

                            <option value="HDD">
                                HDD
                            </option>

                            <option value="NVME">
                                NVMe
                            </option>

                            <option value="EMMC">
                                eMMC
                            </option>
                        </select>

                        <input
                            name="especificacion_esperada[tarjeta_grafica]"
                            placeholder="Tarjeta gráfica"
                            class="input-oneshop"
                        >

                        <input
                            type="number"
                            min="0"
                            step="0.1"
                            name="especificacion_esperada[pantalla_pulgadas]"
                            placeholder="Pantalla (pulgadas)"
                            class="input-oneshop"
                        >

                        <input
                            name="especificacion_esperada[resolucion]"
                            placeholder="Resolución"
                            class="input-oneshop"
                        >

                        <input
                            name="especificacion_esperada[sistema_operativo]"
                            placeholder="Sistema operativo"
                            class="input-oneshop"
                        >

                    </div>


                    {{-- ACCESORIOS --}}
                    <div
                        class="
                            mt-5
                            border-t
                            border-slate-200
                            pt-4
                        "
                    >

                        <div
                            class="
                                flex
                                flex-col
                                gap-3
                                sm:flex-row
                                sm:items-center
                                sm:justify-between
                            "
                        >

                            <div>

                                <h4
                                    class="
                                        text-sm
                                        font-bold
                                        text-slate-800
                                    "
                                >
                                    Accesorios por equipo
                                </h4>

                                <p
                                    class="
                                        mt-0.5
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    Por ejemplo: cargador, adaptador o cable.
                                </p>

                            </div>


                            <button
                                type="button"
                                id="agregarComponenteEsperado"
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
                                    text-xs
                                    font-bold
                                    text-oneshop-dark
                                    transition
                                    hover:bg-blue-100
                                "
                            >
                                <x-ui.icon
                                    name="plus"
                                    size="13"
                                />

                                Agregar accesorio
                            </button>

                        </div>


                        <div
                            id="componentesEsperados"
                            class="mt-3 space-y-3"
                        ></div>

                    </div>

                </div>

            </details>


            {{-- ACCIÓN --}}
            <div
                class="
                    flex
                    justify-end
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

                        px-5
                        py-2.5

                        text-sm
                        font-bold
                        text-oneshop-dark

                        shadow-sm

                        transition

                        hover:bg-blue-100

                        focus:outline-none
                        focus:ring-4
                        focus:ring-blue-100
                    "
                >
                    <x-ui.icon
                        name="plus"
                        size="16"
                    />

                    Agregar producto
                </button>

            </div>

        </form>

    </div>

</section>


<script>
document
    .getElementById('selectProducto')
    .addEventListener(
        'change',
        function () {
            if (this.value === 'crear') {
                window.abrirModalProducto();
                this.value = '';
            }
        }
    );


const monedaDetalle =
    document.getElementById(
        'monedaDetalleLote'
    );

const grupoTipoCambioDetalle =
    document.getElementById(
        'grupoTipoCambioDetalle'
    );

const tipoCambioDetalle =
    document.getElementById(
        'tipoCambioDetalleLote'
    );

const usarReferenciaDetalle =
    document.getElementById(
        'usarReferenciaDetalle'
    );

const tieneReferenciaUsdBob =
    @js((bool) $referenciaUsdBob);


function actualizarTipoCambioDetalle() {

    const codigo =
        monedaDetalle
            .options[
                monedaDetalle.selectedIndex
            ]
            ?.dataset
            .codigo;


    const requiereTipoCambio =
        codigo === 'USD'
        || codigo === 'USDT';


    grupoTipoCambioDetalle
        .classList
        .toggle(
            'hidden',
            !requiereTipoCambio
        );


    tipoCambioDetalle.required =
        requiereTipoCambio;


    usarReferenciaDetalle
        .classList
        .toggle(
            'hidden',
            codigo !== 'USD'
            || !tieneReferenciaUsdBob
        );


    if (!requiereTipoCambio) {
        tipoCambioDetalle.value = '';
    }
}


monedaDetalle.addEventListener(
    'change',
    actualizarTipoCambioDetalle
);


usarReferenciaDetalle.addEventListener(
    'click',
    function () {

        @if($referenciaUsdBob)

            tipoCambioDetalle.value =
                @js(
                    (string)
                    $referenciaUsdBob[
                        'tipo_cambio'
                    ]->valor
                );

            tipoCambioDetalle.focus();

        @endif
    }
);


let indiceComponenteEsperado = 0;


document
    .getElementById(
        'agregarComponenteEsperado'
    )
    .addEventListener(
        'click',
        function () {

            const indice =
                indiceComponenteEsperado++;

            const fila =
                document.createElement(
                    'div'
                );


            fila.className =
                'grid gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3 md:grid-cols-[2fr_110px_2fr_auto]';


            fila.innerHTML = `
                <input
                    name="componentes_esperados[${indice}][nombre]"
                    placeholder="Ej. cargador"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-100"
                    required
                >

                <input
                    type="number"
                    min="1"
                    value="1"
                    name="componentes_esperados[${indice}][cantidad_por_unidad]"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-100"
                    required
                >

                <input
                    name="componentes_esperados[${indice}][observacion]"
                    placeholder="Observación opcional"
                    class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-blue-500 focus:ring-blue-100"
                >

                <button
                    type="button"
                    class="quitar-componente rounded-lg px-3 py-2 text-sm font-bold text-red-700 transition hover:bg-red-50"
                >
                    Quitar
                </button>

                <input
                    type="hidden"
                    name="componentes_esperados[${indice}][incluido_en_compra]"
                    value="1"
                >
            `;


            fila
                .querySelector(
                    '.quitar-componente'
                )
                .addEventListener(
                    'click',
                    () => fila.remove()
                );


            document
                .getElementById(
                    'componentesEsperados'
                )
                .appendChild(
                    fila
                );


            fila
                .querySelector('input')
                .focus();
        }
    );


document
    .getElementById(
        'formAgregarProducto'
    )
    .addEventListener(
        'submit',
        async function (e) {

            e.preventDefault();


            const formulario =
                this;


            const datos =
                new FormData(
                    formulario
                );


            const botonSubmit =
                formulario.querySelector(
                    'button[type="submit"]'
                );


            const textoOriginal =
                botonSubmit.innerHTML;


            botonSubmit.disabled =
                true;


            botonSubmit.classList.add(
                'opacity-60',
                'cursor-not-allowed'
            );


            try {

                const respuesta =
                    await fetch(
                        "{{ route('importaciones.detalles.store', $lote) }}",
                        {
                            method: 'POST',

                            headers: {
                                'X-CSRF-TOKEN':
                                    document
                                        .querySelector(
                                            'meta[name="csrf-token"]'
                                        )
                                        .content,

                                'Accept':
                                    'application/json'
                            },

                            body:
                                datos
                        }
                    );


                const json =
                    await respuesta.json();


                if (!json.ok) {

                    alert(
                        json.message
                        ?? 'Error registrando producto'
                    );

                    return;
                }


                const detalle =
                    json.detalle;


                const pendientes =
                    Math.max(
                        0,
                        Number(
                            detalle.cantidad_esperada
                        )
                        -
                        Number(
                            detalle.cantidad_recibida
                        )
                    );


                const estadoHtml =
                    pendientes > 0
                        ? `
                            <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800">
                                ${pendientes}
                                ${pendientes === 1
                                    ? 'pendiente'
                                    : 'pendientes'}
                            </span>
                        `
                        : `
                            <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                                Completo
                            </span>
                        `;


                const filaExistente =
                    document.querySelector(
                        `[data-detalle-id="${detalle.id}"]`
                    );


                if (filaExistente) {

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


                    filaExistente
                        .querySelector(
                            '.estado-pendiente'
                        )
                        .innerHTML =
                            estadoHtml;

                } else {

                    const fila = `
                        <tr
                            data-detalle-id="${detalle.id}"
                            class="transition duration-150 hover:bg-blue-50/40"
                        >

                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-50 font-bold text-blue-700">
                                        #
                                    </div>

                                    <p class="font-bold text-slate-900">
                                        ${detalle.producto}
                                    </p>

                                </div>
                            </td>


                            <td class="cantidad-esperada px-5 py-4 text-center font-bold text-slate-800">
                                ${detalle.cantidad_esperada}
                            </td>


                            <td class="cantidad-recibida px-5 py-4 text-center font-bold text-slate-800">
                                ${detalle.cantidad_recibida}
                            </td>


                            <td class="estado-pendiente px-5 py-4 text-right">
                                ${estadoHtml}
                            </td>

                        </tr>
                    `;


                    const vacia =
                        document.getElementById(
                            'filaVacia'
                        );


                    if (vacia) {
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


                document
                    .getElementById(
                        'componentesEsperados'
                    )
                    .innerHTML =
                        '';


                actualizarTipoCambioDetalle();

            } catch (error) {

                alert(
                    'No fue posible registrar el producto.'
                );

            } finally {

                botonSubmit.disabled =
                    false;


                botonSubmit.classList.remove(
                    'opacity-60',
                    'cursor-not-allowed'
                );


                botonSubmit.innerHTML =
                    textoOriginal;
            }

        }
    );


actualizarTipoCambioDetalle();
</script>