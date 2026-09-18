<x-layouts.oneshop
    title="Nuevo lote | OneShop"
    page-title="Nuevo lote"
>

    <div class="mx-auto max-w-4xl">

        <div class="mb-6">
            <a
                href="{{ route('importaciones.index') }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-950"
            >
                ← Volver a importaciones
            </a>
        </div>


        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950">
                Registrar lote de importación
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Registra la compra o agrupación logística antes de incorporar sus unidades físicas al inventario.
            </p>
        </div>


        @if($errors->any())
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="font-semibold text-red-800">
                    Revisa la información ingresada.
                </p>

                <ul class="mt-2 list-inside list-disc text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <form
            method="POST"
            action="{{ route('importaciones.store') }}"
            class="space-y-6"
        >
            @csrf

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-slate-950">
                    Identificación del lote
                </h2>

                <div class="mt-6 grid gap-6 md:grid-cols-2">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Código *
                        </label>

                        <input
                            type="text"
                            name="codigo"
                            required
                            maxlength="50"
                            value="{{ old('codigo', $codigoSugerido) }}"
                            placeholder="IMP-2026-001"
                            class="w-full rounded-xl border-slate-300 uppercase focus:border-slate-900 focus:ring-slate-900"
                        >
                        <p class="mt-2 text-xs text-slate-500">
                            Generado automáticamente. Puedes modificarlo si la documentación de compra utiliza otro código.
                        </p>
                    </div>


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Referencia de compra
                        </label>

                        <input
                            type="text"
                            name="referencia_compra"
                            maxlength="100"
                            value="{{ old('referencia_compra') }}"
                            placeholder="Orden, remate, factura..."
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    {{-- Proveedor --}}
<div
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
                                    .querySelector('meta[name=csrf-token]')
                                    .getAttribute('content')
                        },

                        body: JSON.stringify(
                            this.nuevoProveedor
                        )
                    }
                );

                const datos = await respuesta.json();

                if (!respuesta.ok) {

                    if (datos.errors) {
                        const mensajes =
                            Object.values(datos.errors)
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

                this.modalProveedor = false;

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

    <div class="mb-2 flex items-center justify-between gap-3">

        <label class="block text-sm font-semibold text-slate-700">
            Proveedor
        </label>

        <button
            type="button"
            @click="
                limpiarProveedor();
                modalProveedor = true;
            "
            class="text-xs font-semibold text-slate-700 hover:text-slate-950"
        >
            + Nuevo proveedor
        </button>

    </div>


    <select
        id="proveedor_id_lote"
        name="proveedor_id"
        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
    >
        <option value="">
            Sin proveedor definido
        </option>

        @foreach($proveedores as $proveedor)

            <option
                value="{{ $proveedor->id }}"
                @selected(old('proveedor_id') == $proveedor->id)
            >
                {{ $proveedor->nombre }}

                @if($proveedor->pais)
                    — {{ $proveedor->pais }}
                @endif
            </option>

        @endforeach
    </select>


    {{-- Modal nuevo proveedor --}}
    <template x-teleport="body">

        <div
            x-show="modalProveedor"
            x-cloak
            @keydown.escape.window="modalProveedor = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >

            {{-- Fondo --}}
            <div
                class="absolute inset-0 bg-slate-950/50"
                @click="modalProveedor = false"
            ></div>


            {{-- Ventana --}}
            <div
                x-show="modalProveedor"
                x-transition
                class="relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
            >

                {{-- Cabecera --}}
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">

                    <div>
                        <h3 class="text-lg font-bold text-slate-950">
                            Nuevo proveedor
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Registra un proveedor.
                        </p>
                    </div>


                    <button
                        type="button"
                        @click="modalProveedor = false"
                        class="rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900"
                    >
                        ✕
                    </button>

                </div>


                {{-- Contenido --}}
                <div class="space-y-5 p-6">

                    <div
                        x-show="errorProveedor"
                        x-text="errorProveedor"
                        class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                    ></div>


                    <div class="grid gap-5 md:grid-cols-2">

                        {{-- Nombre --}}
                        <div class="md:col-span-2">

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Nombre *
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProveedor.nombre"
                                maxlength="150"
                                placeholder="Ej. Auction Tech LLC"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- País --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                País
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProveedor.pais"
                                maxlength="100"
                                placeholder="Ej. Estados Unidos"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Ciudad --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Ciudad
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProveedor.ciudad"
                                maxlength="100"
                                placeholder="Ej. Houston"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Contacto --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Persona de contacto
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProveedor.contacto"
                                maxlength="150"
                                placeholder="Ej. John Smith"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Teléfono --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Teléfono
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProveedor.telefono"
                                maxlength="30"
                                placeholder="+1 ..."
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Correo --}}
                        <div class="md:col-span-2">

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Correo
                            </label>

                            <input
                                type="email"
                                x-model="nuevoProveedor.correo"
                                maxlength="150"
                                placeholder="ventas@proveedor.com"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>

                    </div>


                    {{-- Observación --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Observación
                        </label>

                        <textarea
                            x-model="nuevoProveedor.observacion"
                            rows="3"
                            placeholder="Información adicional del proveedor..."
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        ></textarea>

                    </div>

                </div>


                {{-- Acciones --}}
                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-5">

                    <button
                        type="button"
                        @click="modalProveedor = false"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancelar
                    </button>


                    <button
                        type="button"
                        @click="crearProveedor()"
                        :disabled="
                            guardandoProveedor
                            || !nuevoProveedor.nombre.trim()
                        "
                        class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >

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

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Origen
                        </label>

                        <input
                            type="text"
                            name="origen"
                            maxlength="150"
                            value="{{ old('origen') }}"
                            placeholder="Miami, Estados Unidos"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>

                </div>


                <div class="mt-6">

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Observación
                    </label>

                    <textarea
                        name="observacion"
                        rows="4"
                        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        placeholder="Información adicional sobre la compra o importación..."
                    >{{ old('observacion') }}</textarea>

                </div>

            </section>


            <div class="flex justify-end gap-3">

                <a
                    href="{{ route('importaciones.index') }}"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Crear lote
                </button>

            </div>

        </form>

    </div>

</x-layouts.oneshop>
