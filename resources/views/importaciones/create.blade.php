<x-layouts.oneshop
    title="Nuevo lote | OneShop"
    page-title="Nuevo lote"
>

<div class="max-w-6xl space-y-6">

    {{-- NAVEGACIÓN --}}
    <div>

        <a
            href="{{ route('importaciones.index') }}"
            class="
                inline-flex
                items-center
                gap-2

                rounded-lg
                border
                border-slate-200

                bg-white

                px-3
                py-2

                text-sm
                font-semibold
                text-slate-700

                shadow-sm

                transition
                duration-200

                hover:border-blue-200
                hover:bg-oneshop-light
                hover:text-oneshop-dark

                focus:outline-none
                focus:ring-4
                focus:ring-blue-100
            "
        >
            <span
                class="
                    font-bold
                    text-oneshop-primary
                "
                aria-hidden="true"
            >
                ←
            </span>

            Volver a importaciones
        </a>

    </div>


    {{-- ENCABEZADO --}}
    <div class="max-w-3xl">

        <p
            class="
                text-xs
                font-bold
                uppercase
                tracking-[0.16em]
                text-oneshop-primary
            "
        >
            Compras e importación
        </p>

        <h1
            class="
                mt-1
                text-3xl
                font-bold
                tracking-tight
                text-slate-950
            "
        >
            Registrar lote de importación
        </h1>

        <p
            class="
                mt-2
                text-sm
                leading-6
                text-slate-500
            "
        >
            Registra los datos principales de la compra para iniciar
            el seguimiento del lote.
        </p>

    </div>


    {{-- ERRORES --}}
    @if($errors->any())

        <div
            class="
                rounded-2xl
                border
                border-red-200

                bg-red-50

                p-5
            "
            role="alert"
        >

            <div class="flex gap-3">

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
                        border-red-200

                        bg-white
                        text-red-700
                    "
                >
                    <x-ui.icon
                        name="x"
                        size="17"
                    />
                </div>


                <div>

                    <p
                        class="
                            font-bold
                            text-red-900
                        "
                    >
                        Revisa la información ingresada
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


    {{-- FORMULARIO --}}
    <form
        method="POST"
        action="{{ route('importaciones.store') }}"
        class="
            w-full
            overflow-hidden

            rounded-2xl
            border
            border-blue-100

            bg-gradient-to-b
            from-white
            via-white
            to-blue-50/30

            shadow-sm
        "
    >
        @csrf


        {{-- CABECERA DEL CARD --}}
        <div
            class="
                flex
                items-start
                gap-3

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
                    name="file"
                    size="20"
                />
            </div>


            <div>

                <h2
                    class="
                        text-lg
                        font-bold
                        text-slate-950
                    "
                >
                    Datos del lote
                </h2>

                <p
                    class="
                        mt-0.5
                        text-sm
                        font-medium
                        text-slate-500
                    "
                >
                    Compra, proveedor y procedencia del lote.
                </p>

            </div>

        </div>


        {{-- CAMPOS --}}
        <div class="p-6">

            <div
                class="
                    grid
                    gap-x-6
                    gap-y-5

                    md:grid-cols-2
                "
            >

                {{-- CÓDIGO --}}
                <div>

                    <label
                        for="codigo"
                        class="
                            mb-2
                            block

                            text-sm
                            font-bold
                            text-slate-700
                        "
                    >
                        Código

                        <span class="text-red-700">
                            *
                        </span>
                    </label>

                    <input
                        id="codigo"
                        type="text"
                        name="codigo"

                        required
                        maxlength="50"

                        value="{{ old('codigo', $codigoSugerido) }}"

                        placeholder="IMP-2026-001"

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
                            uppercase
                            text-slate-900

                            placeholder:font-normal
                            placeholder:text-slate-400

                            focus:border-oneshop-primary
                            focus:outline-none
                            focus:ring-4
                            focus:ring-blue-100
                        "
                    >

                    <p
                        class="
                            mt-2
                            text-xs
                            leading-5
                            text-slate-500
                        "
                    >
                        Generado automáticamente. Puedes modificarlo si tu documentación utiliza otro código.
                    </p>

                </div>


                {{-- FECHA DE COMPRA --}}
                <div>

                    <label
                        for="fecha_compra"
                        class="
                            mb-2
                            block

                            text-sm
                            font-bold
                            text-slate-700
                        "
                    >
                        Fecha de compra
                    </label>

                    <input
                        id="fecha_compra"
                        type="date"
                        name="fecha_compra"

                        value="{{ old('fecha_compra') }}"

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


                {{-- REFERENCIA --}}
                <div>

                    <label
                        for="referencia_compra"
                        class="
                            mb-2
                            block

                            text-sm
                            font-bold
                            text-slate-700
                        "
                    >
                        Referencia de compra
                    </label>

                    <input
                        id="referencia_compra"
                        type="text"
                        name="referencia_compra"

                        maxlength="100"

                        value="{{ old('referencia_compra') }}"

                        placeholder="Orden, remate, factura..."

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


                {{-- ORIGEN --}}
                <div>

                    <label
                        for="origen"
                        class="
                            mb-2
                            block

                            text-sm
                            font-bold
                            text-slate-700
                        "
                    >
                        Origen
                    </label>

                    <input
                        id="origen"
                        type="text"
                        name="origen"

                        maxlength="150"

                        value="{{ old('origen') }}"

                        placeholder="Miami, Estados Unidos"

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


                {{-- PROVEEDOR --}}
                <div
                    class="md:col-span-2"

                    x-data="{
                        modalProveedor: false,
                        guardandoProveedor: false,
                        errorProveedor: '',

                        nuevoProveedor: {
                            nombre: '',
                            pais: '',
                            ciudad: '',
                            contacto: '',
                            telefono: '',
                            correo: '',
                            observacion: ''
                        },

                        limpiarProveedor() {
                            this.errorProveedor = '';

                            this.nuevoProveedor = {
                                nombre: '',
                                pais: '',
                                ciudad: '',
                                contacto: '',
                                telefono: '',
                                correo: '',
                                observacion: ''
                            };
                        },

                        async crearProveedor() {
                            this.guardandoProveedor = true;
                            this.errorProveedor = '';

                            try {

                                const respuesta = await fetch(
                                    '{{ route('importaciones.catalogo.proveedores.store') }}',
                                    {
                                        method: 'POST',

                                        headers: {
                                            'Content-Type': 'application/json',
                                            'Accept': 'application/json',

                                            'X-CSRF-TOKEN':
                                                document
                                                    .querySelector(
                                                        'meta[name=csrf-token]'
                                                    )
                                                    .getAttribute('content')
                                        },

                                        body: JSON.stringify(
                                            this.nuevoProveedor
                                        )
                                    }
                                );


                                const datos =
                                    await respuesta.json();


                                if (!respuesta.ok) {

                                    if (datos.errors) {

                                        const mensajes =
                                            Object
                                                .values(datos.errors)
                                                .flat();

                                        this.errorProveedor =
                                            mensajes.join(' ');

                                    } else {

                                        this.errorProveedor =
                                            datos.message
                                            ?? 'No fue posible crear el proveedor.';

                                    }

                                    return;
                                }


                                const select =
                                    document.getElementById(
                                        'proveedor_id_lote'
                                    );


                                const opcion =
                                    document.createElement(
                                        'option'
                                    );


                                opcion.value =
                                    datos.proveedor.id;


                                opcion.textContent =
                                    datos.proveedor.label;


                                opcion.selected =
                                    true;


                                select.appendChild(
                                    opcion
                                );


                                this.modalProveedor =
                                    false;


                                this.limpiarProveedor();

                            } catch (error) {

                                this.errorProveedor =
                                    'No fue posible comunicarse con el servidor.';

                            } finally {

                                this.guardandoProveedor =
                                    false;

                            }
                        }
                    }"
                >

                    {{-- CABECERA PROVEEDOR --}}
                    <div
                        class="
                            mb-2

                            flex
                            flex-wrap
                            items-center
                            justify-between
                            gap-3
                        "
                    >

                        <label
                            for="proveedor_id_lote"
                            class="
                                block

                                text-sm
                                font-bold
                                text-slate-700
                            "
                        >
                            Proveedor
                        </label>


                        <button
                            type="button"

                            @click="
                                limpiarProveedor();
                                modalProveedor = true;
                            "

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
                                py-1.5

                                text-xs
                                font-bold
                                text-oneshop-dark

                                shadow-sm

                                transition
                                duration-200

                                hover:border-oneshop-primary
                                hover:bg-blue-100

                                focus:outline-none
                                focus:ring-4
                                focus:ring-blue-100
                            "
                        >
                            <x-ui.icon
                                name="plus"
                                size="14"
                            />

                            Nuevo proveedor
                        </button>

                    </div>


                    <select
                        id="proveedor_id_lote"
                        name="proveedor_id"

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
                            Sin proveedor definido
                        </option>

                        @foreach($proveedores as $proveedor)

                            <option
                                value="{{ $proveedor->id }}"

                                @selected(
                                    old('proveedor_id')
                                    == $proveedor->id
                                )
                            >
                                {{ $proveedor->nombre }}

                                @if($proveedor->pais)

                                    — {{ $proveedor->pais }}

                                @endif
                            </option>

                        @endforeach

                    </select>


                    <p
                        class="
                            mt-2
                            text-xs
                            text-slate-500
                        "
                    >
                        Selecciona uno existente o registra uno nuevo sin salir.
                    </p>


                    {{-- MODAL NUEVO PROVEEDOR --}}
                    <template x-teleport="body">

                        <div
                            x-show="modalProveedor"
                            x-cloak

                            @keydown.escape.window="
                                if (!guardandoProveedor) {
                                    modalProveedor = false
                                }
                            "

                            class="
                                fixed
                                inset-0
                                z-50

                                flex
                                items-center
                                justify-center

                                p-4
                            "

                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="titulo-modal-proveedor"
                        >

                            {{-- FONDO --}}
                            <div
                                class="
                                    absolute
                                    inset-0

                                    bg-slate-950/50
                                    backdrop-blur-[1px]
                                "

                                @click="
                                    if (!guardandoProveedor) {
                                        modalProveedor = false
                                    }
                                "
                            ></div>


                            {{-- VENTANA --}}
                            <div
                                x-show="modalProveedor"
                                x-transition

                                class="
                                    relative
                                    z-10

                                    max-h-[90vh]
                                    w-full
                                    max-w-2xl

                                    overflow-y-auto

                                    rounded-2xl
                                    border
                                    border-slate-200

                                    bg-white

                                    shadow-2xl
                                "
                            >

                                {{-- CABECERA --}}
                                <div
                                    class="
                                        flex
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
                                            items-start
                                            gap-3
                                        "
                                    >

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
                                                border-blue-200

                                                bg-white
                                                text-oneshop-primary

                                                shadow-sm
                                            "
                                        >
                                            <x-ui.icon
                                                name="user"
                                                size="19"
                                            />
                                        </div>


                                        <div>

                                            <h3
                                                id="titulo-modal-proveedor"
                                                class="
                                                    text-lg
                                                    font-bold
                                                    text-slate-950
                                                "
                                            >
                                                Nuevo proveedor
                                            </h3>

                                            <p
                                                class="
                                                    mt-0.5
                                                    text-sm
                                                    text-slate-500
                                                "
                                            >
                                                Registra los datos básicos
                                                del proveedor.
                                            </p>

                                        </div>

                                    </div>


                                    <button
                                        type="button"

                                        @click="
                                            modalProveedor = false
                                        "

                                        :disabled="
                                            guardandoProveedor
                                        "

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

                                            disabled:cursor-not-allowed
                                            disabled:opacity-50
                                        "

                                        aria-label="Cerrar"
                                    >
                                        <x-ui.icon
                                            name="x"
                                            size="17"
                                        />
                                    </button>

                                </div>


                                {{-- CONTENIDO MODAL --}}
                                <div class="space-y-5 p-6">

                                    <div
                                        x-show="errorProveedor"
                                        x-text="errorProveedor"

                                        class="
                                            rounded-xl
                                            border
                                            border-red-200

                                            bg-red-50

                                            p-3

                                            text-sm
                                            font-medium
                                            text-red-800
                                        "

                                        role="alert"
                                    ></div>


                                    <div
                                        class="
                                            grid
                                            gap-5

                                            md:grid-cols-2
                                        "
                                    >

                                        {{-- NOMBRE --}}
                                        <div class="md:col-span-2">

                                            <label
                                                class="
                                                    mb-2
                                                    block

                                                    text-sm
                                                    font-bold
                                                    text-slate-700
                                                "
                                            >
                                                Nombre

                                                <span class="text-red-700">
                                                    *
                                                </span>
                                            </label>

                                            <input
                                                type="text"
                                                x-model="nuevoProveedor.nombre"

                                                maxlength="150"

                                                placeholder="Ej. Auction Tech LLC"

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

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


                                        {{-- PAÍS --}}
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
                                                País
                                            </label>

                                            <input
                                                type="text"
                                                x-model="nuevoProveedor.pais"

                                                maxlength="100"

                                                placeholder="Ej. Estados Unidos"

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

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


                                        {{-- CIUDAD --}}
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
                                                Ciudad
                                            </label>

                                            <input
                                                type="text"
                                                x-model="nuevoProveedor.ciudad"

                                                maxlength="100"

                                                placeholder="Ej. Houston"

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

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


                                        {{-- CONTACTO --}}
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
                                                Persona de contacto
                                            </label>

                                            <input
                                                type="text"
                                                x-model="nuevoProveedor.contacto"

                                                maxlength="150"

                                                placeholder="Ej. John Smith"

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

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


                                        {{-- TELÉFONO --}}
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
                                                Teléfono
                                            </label>

                                            <input
                                                type="text"
                                                x-model="nuevoProveedor.telefono"

                                                maxlength="30"

                                                placeholder="+1 ..."

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

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


                                        {{-- CORREO --}}
                                        <div class="md:col-span-2">

                                            <label
                                                class="
                                                    mb-2
                                                    block

                                                    text-sm
                                                    font-bold
                                                    text-slate-700
                                                "
                                            >
                                                Correo
                                            </label>

                                            <input
                                                type="email"
                                                x-model="nuevoProveedor.correo"

                                                maxlength="150"

                                                placeholder="ventas@proveedor.com"

                                                class="
                                                    w-full

                                                    rounded-xl
                                                    border
                                                    border-slate-300

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


                                    {{-- OBSERVACIÓN PROVEEDOR --}}
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
                                            Observación
                                        </label>

                                        <textarea
                                            x-model="nuevoProveedor.observacion"

                                            rows="3"

                                            placeholder="Información adicional del proveedor..."

                                            class="
                                                w-full
                                                resize-y

                                                rounded-xl
                                                border
                                                border-slate-300

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
                                        ></textarea>

                                    </div>

                                </div>


                                {{-- ACCIONES MODAL --}}
                                <div
                                    class="
                                        flex
                                        flex-col-reverse
                                        gap-3

                                        border-t
                                        border-blue-100

                                        bg-blue-50/40

                                        px-6
                                        py-5

                                        sm:flex-row
                                        sm:justify-end
                                    "
                                >

                                    <button
                                        type="button"

                                        @click="
                                            modalProveedor = false
                                        "

                                        :disabled="
                                            guardandoProveedor
                                        "

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

                                            disabled:cursor-not-allowed
                                            disabled:opacity-50
                                        "
                                    >
                                        Cancelar
                                    </button>


                                    <button
                                        type="button"

                                        @click="
                                            crearProveedor()
                                        "

                                        :disabled="
                                            guardandoProveedor
                                            || !nuevoProveedor.nombre.trim()
                                        "

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

                                            transition

                                            hover:bg-blue-100

                                            focus:outline-none
                                            focus:ring-4
                                            focus:ring-blue-100

                                            disabled:cursor-not-allowed
                                            disabled:border-slate-200
                                            disabled:bg-slate-100
                                            disabled:text-slate-400
                                        "
                                    >

                                        <x-ui.icon
                                            name="plus"
                                            size="16"
                                        />

                                        <span x-show="!guardandoProveedor">
                                            Crear y seleccionar
                                        </span>

                                        <span x-show="guardandoProveedor">
                                            Guardando...
                                        </span>

                                    </button>

                                </div>

                            </div>

                        </div>

                    </template>

                </div>


                {{-- OBSERVACIÓN DEL LOTE --}}
                <div class="md:col-span-2">

                    <div
                        class="
                            mt-1
                            border-t
                            border-blue-50
                            pt-5
                        "
                    >

                        <label
                            for="observacion"
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
                            id="observacion"
                            name="observacion"

                            rows="3"

                            placeholder="Información adicional sobre la compra o importación..."

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

                        <p
                            class="
                                mt-2
                                text-xs
                                text-slate-500
                            "
                        >
                            Opcional. Úsalo para información útil durante
                            el seguimiento del lote.
                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- PIE / ACCIONES --}}
        <div
            class="
                flex
                flex-col-reverse
                gap-3

                border-t
                border-blue-100

                bg-blue-50/40

                px-6
                py-5

                sm:flex-row
                sm:items-center
                sm:justify-between
            "
        >

            <p
                class="
                    text-xs
                    text-slate-500
                "
            >
                Los datos de composición se registran después de crear el lote.
            </p>


            <div
                class="
                    flex
                    flex-col-reverse
                    gap-3

                    sm:flex-row
                    sm:items-center
                "
            >

                <a
                    href="{{ route('importaciones.index') }}"
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

                        hover:border-slate-400
                        hover:bg-slate-100

                        focus:outline-none
                        focus:ring-4
                        focus:ring-slate-100
                    "
                >
                    Cancelar
                </a>


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

                    Crear lote
                </button>

            </div>

        </div>

    </form>

</div>

</x-layouts.oneshop>