<div
    id="modalRegistrarEquipo"

    class="
        fixed
        inset-0
        z-50

        {{ old('_form_context') === 'recepcion_unidad'
            ? ''
            : 'hidden' }}

        bg-slate-950/50
        backdrop-blur-sm

        p-4
    "

    onclick="cerrarModalEquipoDesdeFondo(event)"
>

    <div
        class="
            flex
            min-h-full
            items-center
            justify-center
        "
    >

        <div
            id="ventanaRegistrarEquipo"

            class="
                flex
                max-h-[94vh]
                w-full
                max-w-5xl
                flex-col

                overflow-hidden

                rounded-2xl
                border
                border-blue-100

                bg-white

                shadow-2xl
            "
        >

            {{-- ========================================================= --}}
            {{-- CABECERA --}}
            {{-- ========================================================= --}}

            <div
                class="
                    flex
                    shrink-0
                    items-start
                    justify-between
                    gap-4

                    border-b
                    border-blue-100

                    bg-gradient-to-r
                    from-blue-50
                    via-oneshop-soft
                    to-white

                    px-6
                    py-5
                "
            >

                <div
                    class="
                        flex
                        min-w-0
                        items-start
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


                    <div class="min-w-0">

                        <p
                            class="
                                text-xs
                                font-bold
                                uppercase
                                tracking-[0.14em]
                                text-oneshop-primary
                            "
                        >
                            Recepción física
                        </p>

                        <h2
                            class="
                                mt-0.5
                                text-xl
                                font-bold
                                text-slate-950
                            "
                        >
                            Registrar equipo recibido
                        </h2>

                        <p
                            class="
                                mt-1
                                text-sm
                                leading-5
                                text-slate-500
                            "
                        >
                            Verifica la unidad física y corrige únicamente
                            los datos que sean diferentes a lo esperado.
                        </p>

                    </div>

                </div>


                <button
                    type="button"

                    onclick="cerrarModalEquipo()"

                    class="
                        flex
                        h-9
                        w-9
                        shrink-0
                        items-center
                        justify-center

                        rounded-lg

                        text-slate-500

                        transition

                        hover:bg-blue-100
                        hover:text-slate-900

                        focus:outline-none
                        focus:ring-4
                        focus:ring-blue-100
                    "

                    aria-label="Cerrar"
                >
                    <x-ui.icon
                        name="x"
                        size="17"
                    />
                </button>

            </div>


            {{-- ========================================================= --}}
            {{-- FORMULARIO --}}
            {{-- ========================================================= --}}

            <form
                id="formRegistrarEquipo"

                method="POST"

                action="{{ route('importaciones.unidades.store', $lote) }}"

                class="
                    flex
                    min-h-0
                    flex-1
                    flex-col
                "
            >
                @csrf


                <input
                    type="hidden"
                    name="_form_context"
                    value="recepcion_unidad"
                >


                <input
                    type="hidden"
                    name="cantidad"
                    value="1"
                >


                {{-- ===================================================== --}}
                {{-- CUERPO SCROLL --}}
                {{-- ===================================================== --}}

                <div
                    id="contenidoRegistrarEquipo"

                    class="
                        min-h-0
                        flex-1
                        overflow-y-auto

                        px-6
                        py-6
                    "
                >

                    <div class="space-y-6">

                        {{-- ÉXITO DE REGISTRO ANTERIOR --}}
                        @if(
                            request()->boolean('registrar_equipo')
                            && session('success')
                        )

                            <div
                                class="
                                    rounded-xl
                                    border
                                    border-emerald-200

                                    bg-emerald-50

                                    p-4
                                "
                            >

                                <div
                                    class="
                                        flex
                                        items-start
                                        gap-3
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center

                                            rounded-lg
                                            border
                                            border-emerald-200

                                            bg-white
                                            text-emerald-700
                                        "
                                    >
                                        <x-ui.icon
                                            name="check"
                                            size="15"
                                        />
                                    </div>

                                    <div>

                                        <p
                                            class="
                                                text-sm
                                                font-bold
                                                text-emerald-900
                                            "
                                        >
                                            Unidad anterior registrada
                                        </p>

                                        <p
                                            class="
                                                mt-0.5
                                                text-sm
                                                text-emerald-800
                                            "
                                        >
                                            {{ session('success') }}
                                        </p>

                                    </div>

                                </div>

                            </div>

                        @endif


                        {{-- ERRORES DE LARAVEL --}}
                        @if(
                            old('_form_context')
                            === 'recepcion_unidad'
                            && $errors->any()
                        )

                            <div
                                class="
                                    rounded-xl
                                    border
                                    border-red-200

                                    bg-red-50

                                    p-4
                                "
                                role="alert"
                            >

                                <div
                                    class="
                                        flex
                                        items-start
                                        gap-3
                                    "
                                >

                                    <div
                                        class="
                                            flex
                                            h-8
                                            w-8
                                            shrink-0
                                            items-center
                                            justify-center

                                            rounded-lg
                                            border
                                            border-red-200

                                            bg-white
                                            text-red-700
                                        "
                                    >
                                        <x-ui.icon
                                            name="x"
                                            size="15"
                                        />
                                    </div>


                                    <div class="min-w-0">

                                        <p
                                            class="
                                                text-sm
                                                font-bold
                                                text-red-900
                                            "
                                        >
                                            Revisa la información del equipo
                                        </p>

                                        <ul
                                            class="
                                                mt-2
                                                list-inside
                                                list-disc
                                                space-y-1

                                                text-sm
                                                text-red-800
                                            "
                                        >
                                            @foreach($errors->all() as $error)

                                                <li>
                                                    {{ $error }}
                                                </li>

                                            @endforeach
                                        </ul>

                                    </div>

                                </div>

                            </div>

                        @endif


                        {{-- ================================================= --}}
                        {{-- PRODUCTO DEL LOTE --}}
                        {{-- ================================================= --}}

                        <section>

                            <div
                                class="
                                    mb-3
                                    flex
                                    flex-wrap
                                    items-end
                                    justify-between
                                    gap-3
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-bold
                                            uppercase
                                            tracking-[0.12em]
                                            text-oneshop-primary
                                        "
                                    >
                                        Producto del lote
                                    </p>

                                    <h3
                                        class="
                                            mt-0.5
                                            text-base
                                            font-bold
                                            text-slate-950
                                        "
                                    >
                                        ¿Qué unidad estás recibiendo?
                                    </h3>

                                </div>

                                <span
                                    class="
                                        rounded-full
                                        border
                                        border-blue-100

                                        bg-blue-50

                                        px-2.5
                                        py-1

                                        text-xs
                                        font-semibold
                                        text-oneshop-dark
                                    "
                                >
                                    1 unidad por registro
                                </span>

                            </div>


                            <label
                                for="detalleLoteRecepcion"

                                class="
                                    mb-2
                                    block

                                    text-sm
                                    font-bold
                                    text-slate-700
                                "
                            >
                                Producto

                                <span class="text-red-700">
                                    *
                                </span>
                            </label>


                            <select
                                name="detalle_lote_id"
                                id="detalleLoteRecepcion"

                                required

                                class="
                                    w-full

                                    rounded-xl
                                    border
                                    border-slate-300

                                    bg-white

                                    px-3.5
                                    py-3

                                    text-sm
                                    font-semibold
                                    text-slate-900

                                    focus:border-oneshop-primary
                                    focus:outline-none
                                    focus:ring-4
                                    focus:ring-blue-100
                                "
                            >

                                <option value="">
                                    Selecciona un producto pendiente de recepción
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


                                        $esperada =
                                            $detalle
                                                ->especificacionEsperada;


                                        $datosRecepcion = [

                                            'campos' => [

                                                'procesador' =>
                                                    $esperada?->procesador,

                                                'generacion_procesador' =>
                                                    $esperada?->generacion_procesador,

                                                'ram_gb' =>
                                                    $esperada?->ram_gb,

                                                'almacenamiento_gb' =>
                                                    $esperada?->almacenamiento_gb,

                                                'tipo_almacenamiento' =>
                                                    $esperada?->tipo_almacenamiento,

                                                'tarjeta_grafica' =>
                                                    $esperada?->tarjeta_grafica,

                                                'sistema_operativo' =>
                                                    $esperada?->sistema_operativo,

                                                'resolucion' =>
                                                    $esperada?->resolucion,

                                                'pantalla_pulgadas' =>
                                                    $esperada?->pantalla_pulgadas,
                                            ],


                                            'compra' => [

                                                'precio' =>
                                                    $detalle->costo_unitario_origen,

                                                'moneda' =>
                                                    $detalle->moneda?->codigo,

                                                'tipo_cambio' =>
                                                    $detalle
                                                        ->tipoCambioCompra
                                                        ?->valor,

                                                'costo_bob' =>
                                                    $detalle->costo_unitario_bob,

                                                'fecha' =>
                                                    $lote
                                                        ->fecha_compra
                                                        ?->format('d/m/Y'),

                                                'referencia' =>
                                                    $lote->referencia_compra,

                                                'proveedor' =>
                                                    $lote
                                                        ->proveedor
                                                        ?->nombre,
                                            ],
                                        ];
                                    @endphp


                                    @if($disponibles > 0)

                                        <option
                                            value="{{ $detalle->id }}"

                                            data-recepcion="{{ json_encode($datosRecepcion) }}"

                                            @selected(
                                                old('detalle_lote_id')
                                                == $detalle->id
                                            )
                                        >
                                            {{ $detalle->producto?->marca?->nombre }}

                                            {{ $detalle->producto?->nombre }}

                                            {{ $detalle->producto?->modelo }}

                                            — {{ $disponibles }}
                                            {{ $disponibles === 1 ? 'pendiente' : 'pendientes' }}
                                        </option>

                                    @endif

                                @endforeach

                            </select>


                            <p
                                class="
                                    mt-2
                                    text-xs
                                    leading-5
                                    text-slate-500
                                "
                            >
                                Al seleccionar el producto, OneShop cargará
                                automáticamente la configuración esperada.
                            </p>

                        </section>


                        {{-- ================================================= --}}
                        {{-- ORIGEN DE COMPRA COMPACTO --}}
                        {{-- ================================================= --}}

                        <section
                            id="resumenCompraRecepcion"

                            class="
                                hidden

                                overflow-hidden

                                rounded-xl
                                border
                                border-blue-100

                                bg-blue-50/40
                            "
                        >

                            <div
                                class="
                                    flex
                                    flex-col
                                    gap-2

                                    border-b
                                    border-blue-100

                                    px-4
                                    py-3

                                    sm:flex-row
                                    sm:items-center
                                    sm:justify-between
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-bold
                                            uppercase
                                            tracking-wide
                                            text-oneshop-primary
                                        "
                                    >
                                       Origen de compra
                                    </p>

                                    <p
                                        class="
                                            mt-0.5
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                      En recepción no se modifica el precio, la moneda ni el tipo de cambio.
                                    </p>

                                </div>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-px

                                    bg-blue-100

                                    sm:grid-cols-2
                                    xl:grid-cols-5
                                "
                            >

                                <div class="bg-white/90 px-4 py-3">

                                    <p
                                        class="
                                            text-[10px]
                                            font-bold
                                            uppercase
                                            tracking-wide
                                            text-slate-500
                                        "
                                    >
                                        Precio
                                    </p>

                                    <p
                                        id="compraPrecioRecepcion"

                                        class="
                                            mt-1
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        —
                                    </p>

                                </div>


                                <div class="bg-white/90 px-4 py-3">

                                    <p
                                        class="
                                            text-[10px]
                                            font-bold
                                            uppercase
                                            tracking-wide
                                            text-slate-500
                                        "
                                    >
                                        Equivalente
                                    </p>

                                    <p
                                        id="compraCostoBobRecepcion"

                                        class="
                                            mt-1
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        —
                                    </p>

                                </div>


                                <div class="bg-white/90 px-4 py-3">

                                    <p
                                        class="
                                            text-[10px]
                                            font-bold
                                            uppercase
                                            tracking-wide
                                            text-slate-500
                                        "
                                    >
                                        Tipo de cambio
                                    </p>

                                    <p
                                        id="compraTipoCambioRecepcion"

                                        class="
                                            mt-1
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        —
                                    </p>

                                </div>


                                <div class="bg-white/90 px-4 py-3">

                                    <p
                                        class="
                                            text-[10px]
                                            font-bold
                                            uppercase
                                            tracking-wide
                                            text-slate-500
                                        "
                                    >
                                        Proveedor
                                    </p>

                                    <p
                                        id="compraProveedorRecepcion"

                                        class="
                                            mt-1
                                            truncate
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        —
                                    </p>

                                </div>


                                <div class="bg-white/90 px-4 py-3">

                                    <p
                                        class="
                                            text-[10px]
                                            font-bold
                                            uppercase
                                            tracking-wide
                                            text-slate-500
                                        "
                                    >
                                        Fecha
                                    </p>

                                    <p
                                        id="compraFechaRecepcion"

                                        class="
                                            mt-1
                                            text-sm
                                            font-bold
                                            text-slate-900
                                        "
                                    >
                                        —
                                    </p>

                                </div>

                            </div>


                            <div
                                id="compraReferenciaContenedor"

                                class="
                                    hidden

                                    border-t
                                    border-blue-100

                                    px-4
                                    py-3
                                "
                            >

                                <span
                                    class="
                                        text-xs
                                        font-semibold
                                        text-slate-500
                                    "
                                >
                                    Referencia:
                                </span>

                                <span
                                    id="compraReferenciaRecepcion"

                                    class="
                                        ml-1
                                        text-xs
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    —
                                </span>

                            </div>


                            <div
                                id="compraIncompletaRecepcion"

                                class="
                                    hidden

                                    border-t
                                    border-amber-200

                                    bg-amber-50

                                    px-4
                                    py-3

                                    text-xs
                                    font-medium
                                    text-amber-800
                                "
                            >
                                Esta línea no tiene todos los datos económicos
                                de compra. La recepción puede continuar, pero
                                conviene completar la compra antes de cerrar
                                el lote.
                            </div>

                        </section>


                        {{-- ================================================= --}}
                        {{-- IDENTIFICACIÓN --}}
                        {{-- ================================================= --}}

                        <section
                            class="
                                rounded-xl
                                border
                                border-slate-200

                                bg-slate-50/40

                                p-5
                            "
                        >

                            <div class="mb-4">

                                <p
                                    class="
                                        text-xs
                                        font-bold
                                        uppercase
                                        tracking-[0.12em]
                                        text-slate-500
                                    "
                                >
                                    Identificación
                                </p>

                                <h3
                                    class="
                                        mt-0.5
                                        text-base
                                        font-bold
                                        text-slate-950
                                    "
                                >
                                    Datos físicos de la unidad
                                </h3>

                            </div>


                            <div
                                class="
                                    grid
                                    gap-5

                                    lg:grid-cols-2
                                "
                            >

                                {{-- SERIAL --}}
                                <div>

                                    <label
                                        for="serialFabricanteRecepcion"

                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Serial del fabricante
                                    </label>

                                    <input
                                        id="serialFabricanteRecepcion"

                                        name="serial_fabricante"

                                        value="{{ old('serial_fabricante') }}"

                                        maxlength="150"

                                        placeholder="Ej. PF3A1BC2"

                                        autocomplete="off"

                                        class="
                                            w-full

                                            rounded-xl
                                            border
                                            border-slate-300

                                            bg-white

                                            px-3.5
                                            py-2.5

                                            text-sm
                                            font-semibold
                                            text-slate-900

                                            placeholder:font-normal
                                            placeholder:text-slate-400

                                            focus:border-oneshop-primary
                                            focus:outline-none
                                            focus:ring-4
                                            focus:ring-blue-100
                                        "
                                    >

                                </div>


                                {{-- GRADO --}}
                                <div>

                                    <p
                                        class="
                                            mb-2

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Grado recibido

                                        <span class="text-red-700">
                                            *
                                        </span>
                                    </p>


                                    <div
                                        class="
                                            grid
                                            grid-cols-3
                                            gap-2
                                        "
                                    >

                                        <div>

                                            <input
                                                id="gradoRecepcionA"
                                                type="radio"
                                                name="grado_recibido"
                                                value="A"

                                                required

                                                class="peer sr-only"

                                                @checked(
                                                    old('grado_recibido')
                                                    === 'A'
                                                )
                                            >

                                            <label
                                                for="gradoRecepcionA"

                                                class="
                                                    flex
                                                    cursor-pointer
                                                    flex-col
                                                    items-center
                                                    justify-center

                                                    rounded-xl
                                                    border
                                                    border-slate-300

                                                    bg-white

                                                    px-3
                                                    py-2.5

                                                    text-center

                                                    transition

                                                    hover:border-blue-300
                                                    hover:bg-blue-50

                                                    peer-checked:border-oneshop-primary
                                                    peer-checked:bg-blue-50
                                                    peer-checked:ring-2
                                                    peer-checked:ring-blue-100
                                                "
                                            >

                                                <span
                                                    class="
                                                        text-sm
                                                        font-bold
                                                        text-slate-900
                                                    "
                                                >
                                                    A
                                                </span>

                                                <span
                                                    class="
                                                        mt-0.5
                                                        text-[10px]
                                                        text-slate-500
                                                    "
                                                >
                                                    90–100%
                                                </span>

                                            </label>

                                        </div>


                                        <div>

                                            <input
                                                id="gradoRecepcionB"
                                                type="radio"
                                                name="grado_recibido"
                                                value="B"

                                                class="peer sr-only"

                                                @checked(
                                                    old('grado_recibido')
                                                    === 'B'
                                                )
                                            >

                                            <label
                                                for="gradoRecepcionB"

                                                class="
                                                    flex
                                                    cursor-pointer
                                                    flex-col
                                                    items-center
                                                    justify-center

                                                    rounded-xl
                                                    border
                                                    border-slate-300

                                                    bg-white

                                                    px-3
                                                    py-2.5

                                                    text-center

                                                    transition

                                                    hover:border-blue-300
                                                    hover:bg-blue-50

                                                    peer-checked:border-oneshop-primary
                                                    peer-checked:bg-blue-50
                                                    peer-checked:ring-2
                                                    peer-checked:ring-blue-100
                                                "
                                            >

                                                <span
                                                    class="
                                                        text-sm
                                                        font-bold
                                                        text-slate-900
                                                    "
                                                >
                                                    B
                                                </span>

                                                <span
                                                    class="
                                                        mt-0.5
                                                        text-[10px]
                                                        text-slate-500
                                                    "
                                                >
                                                    70–90%
                                                </span>

                                            </label>

                                        </div>


                                        <div>

                                            <input
                                                id="gradoRecepcionC"
                                                type="radio"
                                                name="grado_recibido"
                                                value="C"

                                                class="peer sr-only"

                                                @checked(
                                                    old('grado_recibido')
                                                    === 'C'
                                                )
                                            >

                                            <label
                                                for="gradoRecepcionC"

                                                class="
                                                    flex
                                                    cursor-pointer
                                                    flex-col
                                                    items-center
                                                    justify-center

                                                    rounded-xl
                                                    border
                                                    border-slate-300

                                                    bg-white

                                                    px-3
                                                    py-2.5

                                                    text-center

                                                    transition

                                                    hover:border-blue-300
                                                    hover:bg-blue-50

                                                    peer-checked:border-oneshop-primary
                                                    peer-checked:bg-blue-50
                                                    peer-checked:ring-2
                                                    peer-checked:ring-blue-100
                                                "
                                            >

                                                <span
                                                    class="
                                                        text-sm
                                                        font-bold
                                                        text-slate-900
                                                    "
                                                >
                                                    C
                                                </span>

                                                <span
                                                    class="
                                                        mt-0.5
                                                        text-[10px]
                                                        text-slate-500
                                                    "
                                                >
                                                    50–70%
                                                </span>

                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </section>


                        {{-- ================================================= --}}
                        {{-- CONFIGURACIÓN --}}
                        {{-- ================================================= --}}

                        <section
                            class="
                                rounded-xl
                                border
                                border-blue-100

                                bg-blue-50/20

                                p-5
                            "
                        >

                            <div
                                class="
                                    mb-4

                                    flex
                                    flex-col
                                    gap-2

                                    sm:flex-row
                                    sm:items-end
                                    sm:justify-between
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-xs
                                            font-bold
                                            uppercase
                                            tracking-[0.12em]
                                            text-oneshop-primary
                                        "
                                    >
                                        Configuración recibida
                                    </p>

                                    <h3
                                        class="
                                            mt-0.5
                                            text-base
                                            font-bold
                                            text-slate-950
                                        "
                                    >
                                        Verifica el hardware
                                    </h3>

                                </div>


                                <p
                                    class="
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    Los datos esperados se precargan automáticamente.
                                </p>

                            </div>


                            {{-- CPU / GENERACIÓN --}}
                            <div
                                class="
                                    grid
                                    gap-4

                                    md:grid-cols-2
                                "
                            >

                                <div>

                                    <label
                                        for="procesadorRecepcion"

                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Procesador

                                        <span class="text-red-700">
                                            *
                                        </span>
                                    </label>

                                    <input
                                        id="procesadorRecepcion"

                                        name="procesador"

                                        value="{{ old('procesador') }}"

                                        required
                                        maxlength="150"

                                        placeholder="Ej. Core i5"

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


                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Generación
                                    </label>

                                    <input
                                        name="generacion_procesador"

                                        value="{{ old('generacion_procesador') }}"

                                        maxlength="80"

                                        placeholder="Ej. 11th"

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

                            </div>


                            {{-- RAM / DISCO / TIPO --}}
                            <div
                                class="
                                    mt-4

                                    grid
                                    gap-4

                                    sm:grid-cols-2
                                    xl:grid-cols-3
                                "
                            >

                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        RAM
                                    </label>

                                    <div class="relative">

                                        <input
                                            type="number"

                                            name="ram_gb"

                                            value="{{ old('ram_gb') }}"

                                            min="0"
                                            max="65535"

                                            placeholder="16"

                                            class="
                                                w-full

                                                rounded-xl
                                                border
                                                border-slate-300

                                                bg-white

                                                px-3.5
                                                py-2.5
                                                pr-12

                                                text-sm
                                                text-slate-900

                                                placeholder:text-slate-400

                                                focus:border-oneshop-primary
                                                focus:outline-none
                                                focus:ring-4
                                                focus:ring-blue-100
                                            "
                                        >

                                        <span
                                            class="
                                                pointer-events-none
                                                absolute
                                                right-3
                                                top-1/2
                                                -translate-y-1/2

                                                text-xs
                                                font-semibold
                                                text-slate-400
                                            "
                                        >
                                            GB
                                        </span>

                                    </div>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Almacenamiento
                                    </label>

                                    <div class="relative">

                                        <input
                                            type="number"

                                            name="almacenamiento_gb"

                                            value="{{ old('almacenamiento_gb') }}"

                                            min="0"

                                            placeholder="512"

                                            class="
                                                w-full

                                                rounded-xl
                                                border
                                                border-slate-300

                                                bg-white

                                                px-3.5
                                                py-2.5
                                                pr-12

                                                text-sm
                                                text-slate-900

                                                placeholder:text-slate-400

                                                focus:border-oneshop-primary
                                                focus:outline-none
                                                focus:ring-4
                                                focus:ring-blue-100
                                            "
                                        >

                                        <span
                                            class="
                                                pointer-events-none
                                                absolute
                                                right-3
                                                top-1/2
                                                -translate-y-1/2

                                                text-xs
                                                font-semibold
                                                text-slate-400
                                            "
                                        >
                                            GB
                                        </span>

                                    </div>

                                </div>


                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Tipo
                                    </label>

                                    <select
                                        name="tipo_almacenamiento"

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

                                        <option
                                            value="SSD"

                                            @selected(
                                                old('tipo_almacenamiento')
                                                === 'SSD'
                                            )
                                        >
                                            SSD
                                        </option>

                                        <option
                                            value="NVME"

                                            @selected(
                                                old('tipo_almacenamiento')
                                                === 'NVME'
                                            )
                                        >
                                            NVMe
                                        </option>

                                        <option
                                            value="HDD"

                                            @selected(
                                                old('tipo_almacenamiento')
                                                === 'HDD'
                                            )
                                        >
                                            HDD
                                        </option>

                                        <option
                                            value="EMMC"

                                            @selected(
                                                old('tipo_almacenamiento')
                                                === 'EMMC'
                                            )
                                        >
                                            eMMC
                                        </option>

                                    </select>

                                </div>

                            </div>


                            {{-- GPU / CARGADOR --}}
                            <div
                                class="
                                    mt-4

                                    grid
                                    gap-4

                                    lg:grid-cols-2
                                "
                            >

                                <div>

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Tarjeta gráfica
                                    </label>

                                    <input
                                        name="tarjeta_grafica"

                                        value="{{ old('tarjeta_grafica') }}"

                                        maxlength="150"

                                        placeholder="Ej. Intel Iris Xe"

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


                                <div>

                                    <p
                                        class="
                                            mb-2

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Cargador

                                        <span class="text-red-700">
                                            *
                                        </span>
                                    </p>


                                    <div
                                        class="
                                            grid
                                            grid-cols-2
                                            gap-2
                                        "
                                    >

                                        <div>

                                            <input
                                                id="cargadorRecepcionSi"

                                                type="radio"

                                                name="tiene_cargador"

                                                value="1"

                                                required

                                                class="peer sr-only"

                                                @checked(
                                                    old('tiene_cargador')
                                                    === '1'
                                                )
                                            >

                                            <label
                                                for="cargadorRecepcionSi"

                                                class="
                                                    flex
                                                    cursor-pointer
                                                    items-center
                                                    justify-center

                                                    rounded-xl
                                                    border
                                                    border-slate-300

                                                    bg-white

                                                    px-4
                                                    py-2.5

                                                    text-sm
                                                    font-semibold
                                                    text-slate-700

                                                    transition

                                                    hover:border-blue-300
                                                    hover:bg-blue-50

                                                    peer-checked:border-oneshop-primary
                                                    peer-checked:bg-blue-50
                                                    peer-checked:text-oneshop-dark
                                                    peer-checked:ring-2
                                                    peer-checked:ring-blue-100
                                                "
                                            >
                                                Sí tiene
                                            </label>

                                        </div>


                                        <div>

                                            <input
                                                id="cargadorRecepcionNo"

                                                type="radio"

                                                name="tiene_cargador"

                                                value="0"

                                                class="peer sr-only"

                                                @checked(
                                                    old('tiene_cargador')
                                                    === '0'
                                                )
                                            >

                                            <label
                                                for="cargadorRecepcionNo"

                                                class="
                                                    flex
                                                    cursor-pointer
                                                    items-center
                                                    justify-center

                                                    rounded-xl
                                                    border
                                                    border-slate-300

                                                    bg-white

                                                    px-4
                                                    py-2.5

                                                    text-sm
                                                    font-semibold
                                                    text-slate-700

                                                    transition

                                                    hover:border-blue-300
                                                    hover:bg-blue-50

                                                    peer-checked:border-oneshop-primary
                                                    peer-checked:bg-blue-50
                                                    peer-checked:text-oneshop-dark
                                                    peer-checked:ring-2
                                                    peer-checked:ring-blue-100
                                                "
                                            >
                                                No tiene
                                            </label>

                                        </div>

                                    </div>

                                </div>

                            </div>

                        </section>


                        {{-- ================================================= --}}
                        {{-- MÁS DETALLES --}}
                        {{-- ================================================= --}}

                        <details
                            class="
                                group

                                overflow-hidden

                                rounded-xl
                                border
                                border-slate-200

                                bg-white
                            "

                            @if(
                                old('sistema_operativo')
                                || old('resolucion')
                                || old('pantalla_pulgadas')
                                || old('servicio_requerido')
                                || old('observacion')
                            )
                                open
                            @endif
                        >

                            <summary
                                class="
                                    flex
                                    cursor-pointer
                                    list-none
                                    items-center
                                    justify-between
                                    gap-4

                                    px-5
                                    py-4

                                    transition

                                    hover:bg-slate-50
                                "
                            >

                                <div>

                                    <p
                                        class="
                                            text-sm
                                            font-bold
                                            text-slate-800
                                        "
                                    >
                                        Más detalles de recepción
                                    </p>

                                    <p
                                        class="
                                            mt-0.5
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Sistema operativo, pantalla, servicio
                                        y observaciones.
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
                                    aria-hidden="true"
                                >
                                    +
                                </span>

                            </summary>


                            <div
                                class="
                                    border-t
                                    border-slate-200

                                    bg-slate-50/30

                                    p-5
                                "
                            >

                                <div
                                    class="
                                        grid
                                        gap-4

                                        md:grid-cols-2
                                        xl:grid-cols-3
                                    "
                                >

                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block

                                                text-sm
                                                font-bold
                                                text-slate-700
                                            "
                                        >
                                            Sistema operativo
                                        </label>

                                        <input
                                            name="sistema_operativo"

                                            value="{{ old('sistema_operativo') }}"

                                            maxlength="100"

                                            placeholder="Ej. Windows 11 Pro"

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


                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block

                                                text-sm
                                                font-bold
                                                text-slate-700
                                            "
                                        >
                                            Resolución
                                        </label>

                                        <input
                                            name="resolucion"

                                            value="{{ old('resolucion') }}"

                                            maxlength="50"

                                            placeholder="Ej. 1920x1080"

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


                                    <div>

                                        <label
                                            class="
                                                mb-2
                                                block

                                                text-sm
                                                font-bold
                                                text-slate-700
                                            "
                                        >
                                            Pantalla
                                        </label>

                                        <div class="relative">

                                            <input
                                                type="number"

                                                name="pantalla_pulgadas"

                                                value="{{ old('pantalla_pulgadas') }}"

                                                min="0"
                                                step="0.1"

                                                placeholder="14"

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

                                                    bg-white

                                                    px-3.5
                                                    py-2.5
                                                    pr-12

                                                    text-sm
                                                    text-slate-900

                                                    placeholder:text-slate-400

                                                    focus:border-oneshop-primary
                                                    focus:outline-none
                                                    focus:ring-4
                                                    focus:ring-blue-100
                                                "
                                            >

                                            <span
                                                class="
                                                    pointer-events-none
                                                    absolute
                                                    right-3
                                                    top-1/2
                                                    -translate-y-1/2

                                                    text-xs
                                                    font-semibold
                                                    text-slate-400
                                                "
                                            >
                                                "
                                            </span>

                                        </div>

                                    </div>

                                </div>


                                <div class="mt-4">

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Servicio requerido
                                    </label>

                                    <input
                                        name="servicio_requerido"

                                        value="{{ old('servicio_requerido') }}"

                                        maxlength="255"

                                        placeholder="Ej. cambio de teclado, limpieza, revisión de batería..."

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

                                    <p
                                        class="
                                            mt-1.5
                                            text-xs
                                            text-slate-500
                                        "
                                    >
                                        Déjalo vacío si la unidad no necesita
                                        ninguna intervención adicional.
                                    </p>

                                </div>


                                <div class="mt-4">

                                    <label
                                        class="
                                            mb-2
                                            block

                                            text-sm
                                            font-bold
                                            text-slate-700
                                        "
                                    >
                                        Observación
                                    </label>

                                    <textarea
                                        name="observacion"

                                        rows="3"

                                        maxlength="1000"

                                        placeholder="Observaciones importantes de la recepción..."

                                        class="
                                            w-full
                                            resize-y

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
                                    >{{ old('observacion') }}</textarea>

                                </div>

                            </div>

                        </details>

                    </div>

                </div>


                {{-- ===================================================== --}}
                {{-- ACCIONES FIJAS --}}
                {{-- ===================================================== --}}

                <div
                    class="
                        shrink-0

                        border-t
                        border-blue-100

                        bg-blue-50/40

                        px-6
                        py-4
                    "
                >

                    <div
                        class="
                            flex
                            flex-col-reverse
                            gap-3

                            sm:flex-row
                            sm:items-center
                            sm:justify-between
                        "
                    >

                        <button
                            type="button"

                            onclick="cerrarModalEquipo()"

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

                                hover:bg-slate-100

                                focus:outline-none
                                focus:ring-4
                                focus:ring-slate-100
                            "
                        >
                            Cancelar
                        </button>


                        <div
                            class="
                                flex
                                flex-col
                                gap-3

                                sm:flex-row
                                sm:items-center
                            "
                        >

                            <button
                                type="submit"

                                name="continuar_registro"
                                value="0"

                                class="
                                    inline-flex
                                    items-center
                                    justify-center
                                    gap-2

                                    rounded-xl
                                    border
                                    border-slate-300

                                    bg-white

                                    px-5
                                    py-2.5

                                    text-sm
                                    font-bold
                                    text-slate-700

                                    shadow-sm

                                    transition

                                    hover:border-blue-200
                                    hover:bg-blue-50
                                    hover:text-oneshop-dark

                                    focus:outline-none
                                    focus:ring-4
                                    focus:ring-blue-100
                                "
                            >
                                <x-ui.icon
                                    name="check"
                                    size="16"
                                />

                                Guardar equipo
                            </button>


                            <button
                                type="submit"

                                name="continuar_registro"
                                value="1"

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

                                Guardar y registrar otro
                            </button>

                        </div>

                    </div>

                </div>

            </form>

        </div>

    </div>

</div>


@push('scripts')

<script>
(function () {

    const modal =
        document.getElementById(
            'modalRegistrarEquipo'
        );


    const ventana =
        document.getElementById(
            'ventanaRegistrarEquipo'
        );


    const formulario =
        document.getElementById(
            'formRegistrarEquipo'
        );


    const contenido =
        document.getElementById(
            'contenidoRegistrarEquipo'
        );


    const detalleLoteRecepcion =
        document.getElementById(
            'detalleLoteRecepcion'
        );


    const serialFabricante =
        document.getElementById(
            'serialFabricanteRecepcion'
        );


    const resumenCompra =
        document.getElementById(
            'resumenCompraRecepcion'
        );


    const recepcionTieneOldInput =
        @js(
            old('_form_context')
            === 'recepcion_unidad'
        );


    /*
    |--------------------------------------------------------------------------
    | FORMATEO DE MONTOS
    |--------------------------------------------------------------------------
    */

    function formatearMontoRecepcion(
        valor,
        moneda = ''
    ) {

        if (
            valor === null
            || valor === undefined
            || valor === ''
        ) {
            return '—';
        }


        const numero =
            Number(valor);


        if (Number.isNaN(numero)) {

            return moneda
                ? `${moneda} ${valor}`
                : `${valor}`;
        }


        const formato =
            numero.toLocaleString(
                'es-BO',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            );


        return moneda
            ? `${moneda} ${formato}`
            : formato;
    }


    /*
    |--------------------------------------------------------------------------
    | ASIGNAR TEXTO
    |--------------------------------------------------------------------------
    */

    function asignarTexto(
        id,
        valor
    ) {

        const elemento =
            document.getElementById(id);


        if (!elemento) {
            return;
        }


        elemento.textContent =
            valor || '—';
    }


    /*
    |--------------------------------------------------------------------------
    | RESUMEN DE COMPRA
    |--------------------------------------------------------------------------
    */

    function actualizarResumenCompraRecepcion(
        compra = {}
    ) {

        const existeCompra =
            compra
            && Object.keys(compra).length > 0;


        resumenCompra
            ?.classList
            .toggle(
                'hidden',
                !existeCompra
            );


        if (!existeCompra) {

            asignarTexto(
                'compraPrecioRecepcion',
                '—'
            );

            asignarTexto(
                'compraCostoBobRecepcion',
                '—'
            );

            asignarTexto(
                'compraTipoCambioRecepcion',
                '—'
            );

            asignarTexto(
                'compraProveedorRecepcion',
                '—'
            );

            asignarTexto(
                'compraFechaRecepcion',
                '—'
            );

            asignarTexto(
                'compraReferenciaRecepcion',
                '—'
            );

            return;
        }


        asignarTexto(
            'compraPrecioRecepcion',

            formatearMontoRecepcion(
                compra.precio,
                compra.moneda || ''
            )
        );


        asignarTexto(
            'compraCostoBobRecepcion',

            formatearMontoRecepcion(
                compra.costo_bob,
                'BOB'
            )
        );


        asignarTexto(
            'compraTipoCambioRecepcion',

            compra.tipo_cambio
                ? Number(
                    compra.tipo_cambio
                ).toFixed(6)

                : (
                    compra.moneda === 'BOB'
                        ? 'No aplica'
                        : '—'
                )
        );


        asignarTexto(
            'compraProveedorRecepcion',
            compra.proveedor || '—'
        );


        asignarTexto(
            'compraFechaRecepcion',
            compra.fecha || '—'
        );


        asignarTexto(
            'compraReferenciaRecepcion',
            compra.referencia || '—'
        );


        const referenciaContenedor =
            document.getElementById(
                'compraReferenciaContenedor'
            );


        referenciaContenedor
            ?.classList
            .toggle(
                'hidden',
                !compra.referencia
            );


        const advertencia =
            document.getElementById(
                'compraIncompletaRecepcion'
            );


        const compraIncompleta =
            compra.precio === null
            || compra.precio === undefined
            || compra.precio === ''
            || !compra.moneda
            || !compra.fecha;


        advertencia
            ?.classList
            .toggle(
                'hidden',
                !compraIncompleta
            );
    }


    /*
    |--------------------------------------------------------------------------
    | CAMPOS QUE HEREDAN LA CONFIGURACIÓN ESPERADA
    |--------------------------------------------------------------------------
    */

    const camposReferencia = [
        'procesador',
        'generacion_procesador',
        'ram_gb',
        'almacenamiento_gb',
        'tipo_almacenamiento',
        'tarjeta_grafica',
        'sistema_operativo',
        'resolucion',
        'pantalla_pulgadas',
    ];


    function limpiarCamposReferenciaRecepcion()
    {

        camposReferencia.forEach(
            nombre => {

                const campo =
                    formulario
                        ?.elements
                        .namedItem(nombre);


                if (campo) {
                    campo.value = '';
                }
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CARGAR REFERENCIA DEL PRODUCTO
    |--------------------------------------------------------------------------
    */

    function cargarReferenciaRecepcion(
        rellenarCampos = true
    ) {

        if (!detalleLoteRecepcion) {
            return;
        }


        const opcion =
            detalleLoteRecepcion
                .options[
                    detalleLoteRecepcion.selectedIndex
                ];


        if (
            !opcion
            || !opcion.value
        ) {

            if (rellenarCampos) {
                limpiarCamposReferenciaRecepcion();
            }


            actualizarResumenCompraRecepcion(
                {}
            );

            return;
        }


        let datos = {};


        try {

            datos =
                opcion.dataset.recepcion
                    ? JSON.parse(
                        opcion.dataset.recepcion
                    )
                    : {};

        } catch (error) {

            console.error(
                'No fue posible leer los datos de referencia de recepción.',
                error
            );

            datos = {};
        }


        if (rellenarCampos) {

            Object
                .entries(
                    datos.campos || {}
                )
                .forEach(
                    ([nombre, valor]) => {

                        const campo =
                            formulario
                                ?.elements
                                .namedItem(nombre);


                        if (campo) {
                            campo.value =
                                valor ?? '';
                        }
                    }
                );
        }


        actualizarResumenCompraRecepcion(
            datos.compra || {}
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SELECCIONAR PRODUCTO
    |--------------------------------------------------------------------------
    */

    function seleccionarDetalleRecepcion(
        detalleId
    ) {

        if (
            !detalleLoteRecepcion
            || !detalleId
        ) {
            return false;
        }


        const opcion =
            Array
                .from(
                    detalleLoteRecepcion.options
                )
                .find(
                    item =>
                        String(item.value)
                        === String(detalleId)
                );


        if (!opcion) {
            return false;
        }


        detalleLoteRecepcion.value =
            String(detalleId);


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | ABRIR MODAL
    |--------------------------------------------------------------------------
    */

    window.abrirModalEquipo =
        function (
            detalleId = null,
            preservarCampos = false
        ) {

            if (
                !modal
                || !formulario
            ) {
                console.error(
                    'No existe el modal de recepción.'
                );

                return;
            }


            if (!preservarCampos) {

                formulario.reset();

                actualizarResumenCompraRecepcion(
                    {}
                );


                if (contenido) {
                    contenido.scrollTop = 0;
                }
            }


            modal.classList.remove(
                'hidden'
            );


            document
                .body
                .classList
                .add(
                    'overflow-hidden'
                );


            let productoSeleccionado =
                false;


            if (detalleId) {

                productoSeleccionado =
                    seleccionarDetalleRecepcion(
                        detalleId
                    );

            } else if (
                detalleLoteRecepcion
                && detalleLoteRecepcion.options.length === 2
            ) {

                /*
                 * Si solamente existe un producto pendiente,
                 * OneShop lo selecciona automáticamente.
                 */

                detalleLoteRecepcion.selectedIndex =
                    1;

                productoSeleccionado =
                    true;
            }


            if (
                productoSeleccionado
                || detalleLoteRecepcion?.value
            ) {

                cargarReferenciaRecepcion(
                    true
                );

            } else {

                actualizarResumenCompraRecepcion(
                    {}
                );
            }


            window.setTimeout(
                () => {

                    if (
                        detalleLoteRecepcion?.value
                        && serialFabricante
                    ) {

                        serialFabricante.focus();

                    } else {

                        detalleLoteRecepcion
                            ?.focus();
                    }

                },
                80
            );
        };


    /*
    |--------------------------------------------------------------------------
    | CERRAR MODAL
    |--------------------------------------------------------------------------
    */

    window.cerrarModalEquipo =
        function () {

            if (!modal) {
                return;
            }


            modal.classList.add(
                'hidden'
            );


            document
                .body
                .classList
                .remove(
                    'overflow-hidden'
                );
        };


    /*
    |--------------------------------------------------------------------------
    | CERRAR AL HACER CLICK FUERA
    |--------------------------------------------------------------------------
    */

    window.cerrarModalEquipoDesdeFondo =
        function (event) {

            if (
                event.target
                === modal
            ) {

                window
                    .cerrarModalEquipo();
            }
        };


    /*
    |--------------------------------------------------------------------------
    | CAMBIO DE PRODUCTO
    |--------------------------------------------------------------------------
    */

    detalleLoteRecepcion
        ?.addEventListener(
            'change',
            function () {

                cargarReferenciaRecepcion(
                    true
                );


                if (
                    this.value
                    && serialFabricante
                ) {

                    window.setTimeout(
                        () => {
                            serialFabricante.focus();
                        },
                        50
                    );
                }
            }
        );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
                && modal
                && !modal.classList.contains('hidden')
            ) {

                window
                    .cerrarModalEquipo();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CARGA INICIAL
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            /*
             * Caso 1:
             * Laravel regresó con errores de validación.
             *
             * Conservamos exactamente los campos que Hugo había escrito.
             */

            if (recepcionTieneOldInput) {

                modal
                    ?.classList
                    .remove(
                        'hidden'
                    );


                document
                    .body
                    .classList
                    .add(
                        'overflow-hidden'
                    );


                cargarReferenciaRecepcion(
                    false
                );


                if (contenido) {
                    contenido.scrollTop = 0;
                }


                return;
            }


            /*
             * Caso 2:
             * Se utilizó "Guardar y registrar otro".
             */

            const url =
                new URL(
                    window.location.href
                );


            const reabrir =
                url.searchParams.get(
                    'registrar_equipo'
                );


            const detalleId =
                url.searchParams.get(
                    'detalle_lote_id'
                );


            if (reabrir === '1') {

                window
                    .abrirModalEquipo(
                        detalleId,
                        false
                    );


                /*
                 * Eliminamos los parámetros visuales de la URL.
                 * Así un F5 posterior no vuelve a abrir el modal.
                 */

                url.searchParams.delete(
                    'registrar_equipo'
                );


                url.searchParams.delete(
                    'detalle_lote_id'
                );


                window.history.replaceState(
                    {},
                    '',
                    url.toString()
                );


                return;
            }


            /*
             * Estado normal.
             */

            if (
                detalleLoteRecepcion?.value
            ) {

                cargarReferenciaRecepcion(
                    false
                );
            }
        }
    );

})();
</script>

@endpush