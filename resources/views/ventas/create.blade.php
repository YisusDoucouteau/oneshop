<x-layouts.oneshop title="Registrar venta | OneShop" page-title="Nueva venta">
@php
    $equiposAnteriores =
        collect(old('equipos', []))
            ->map(
                fn ($item) =>
                    (int) ($item['equipo_id'] ?? 0)
            )
            ->filter()
            ->values()
            ->all();

    $preciosAnteriores =
        collect(old('equipos', []))
            ->filter(
                fn ($item) =>
                    isset($item['equipo_id'])
                    &&
                    isset($item['precio'])
            )
            ->mapWithKeys(
                fn ($item) => [
                    (int) $item['equipo_id'] =>
                        (float) $item['precio'],
                ]
            )
            ->all();

    $preciosPublicados =
        $equipos
            ->mapWithKeys(
                fn ($equipo) => [
                    $equipo->id =>
                        (float)
                        $equipo
                            ->precioVigente
                            ->precio_publico,
                ]
            )
            ->all();

    $condicionesAnteriores =
        collect(old('equipos', []))
            ->filter(
                fn ($item) =>
                    isset($item['equipo_id'])
            )
            ->mapWithKeys(
                fn ($item) => [
                    (int) $item['equipo_id'] =>
                        strtoupper(
                            (string)
                            ($item['condicion'] ?? 'USADO')
                        ),
                ]
            )
            ->all();

    $clientesVenta =
        $clientes
            ->mapWithKeys(
                fn ($cliente) => [
                    (string) $cliente->id => [
                        'nombre' =>
                            $cliente->nombre_completo,

                        'telefono' =>
                            $cliente->telefono,
                    ],
                ]
            )
            ->all();
@endphp

<div
    class="space-y-6"
    x-data="{
        equipoBusqueda: '',
        clienteId: @js(old('cliente_id')),
        clienteNombre: @js(old('cliente_nombre', '')),
        clienteTelefono: @js(old('cliente_telefono', '')),
        clientes: @js($clientesVenta),
        seleccionados: @js($equiposAnteriores),
        precios: @js($preciosAnteriores),
        publicados: @js($preciosPublicados),
        condiciones: @js($condicionesAnteriores),
        evaluaciones: {},
        cargando: {},
        csrf: @js(csrf_token()),
        evaluarBase: @js(url('/ventas/equipos')),

        aplicarCliente() {
            const cliente =
                this.clientes[
                    String(this.clienteId ?? '')
                ];

            if (!cliente) {
                return;
            }

            this.clienteNombre =
                cliente.nombre
                ?? '';

            this.clienteTelefono =
                cliente.telefono
                ?? '';
        },

        coincide(texto, busqueda) {
            return String(texto ?? '')
                .toLowerCase()
                .includes(
                    String(busqueda ?? '')
                        .trim()
                        .toLowerCase()
                );
        },

        seleccionado(id) {
            return this.seleccionados.includes(Number(id));
        },

        toggleEquipo(id, precio, codigo) {
            id = Number(id);

            if (this.seleccionado(id)) {
                this.seleccionados =
                    this.seleccionados
                        .filter(
                            valor => valor !== id
                        );

                delete this.evaluaciones[id];
                return;
            }

            this.seleccionados.push(id);

            if (
                this.precios[id] === undefined
                ||
                this.precios[id] === null
                ||
                this.precios[id] === ''
            ) {
                this.precios[id] =
                    Number(precio);
            }

            if (
                !this.condiciones[id]
            ) {
                this.condiciones[id] =
                    'USADO';
            }

            this.$nextTick(
                () => this.evaluar(
                    id,
                    codigo
                )
            );
        },

        totalAcordado() {
            return this.seleccionados.reduce(
                (total, id) =>
                    total
                    +
                    Number(
                        this.precios[id]
                        ?? 0
                    ),
                0
            );
        },

        descuentoTotal() {
            return this.seleccionados.reduce(
                (total, id) => {
                    const publicado =
                        Number(
                            this.publicados[id]
                            ?? 0
                        );

                    const acordado =
                        Number(
                            this.precios[id]
                            ?? 0
                        );

                    return total
                        +
                        Math.max(
                            0,
                            publicado
                            -
                            acordado
                        );
                },
                0
            );
        },

        dinero(valor) {
            return new Intl.NumberFormat(
                'es-BO',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(
                Number(valor ?? 0)
            );
        },

        invalidar(id) {
            delete this.evaluaciones[
                Number(id)
            ];
        },

        todosEvaluados() {
            return (
                this.seleccionados.length > 0
                &&
                this.seleccionados.every(
                    id =>
                        this.evaluaciones[id]
                        &&
                        this.evaluaciones[id].ok
                )
            );
        },

        hayBloqueo() {
            return this.seleccionados.some(
                id => {
                    const resultado =
                        this.evaluaciones[id];

                    return (
                        resultado
                        &&
                        resultado.ok
                        &&
                        !resultado.permitido
                        &&
                        !resultado.requiere_aprobacion
                    );
                }
            );
        },

        hayAprobacionPendiente() {
            return this.seleccionados.some(
                id => {
                    const resultado =
                        this.evaluaciones[id];

                    return (
                        resultado
                        &&
                        resultado.ok
                        &&
                        !resultado.permitido
                        &&
                        resultado.requiere_aprobacion
                    );
                }
            );
        },

        async evaluar(id, codigo) {
            id = Number(id);

            if (!this.seleccionado(id)) {
                return;
            }

            const precio =
                Number(
                    this.precios[id]
                    ?? 0
                );

            if (!precio || precio <= 0) {
                this.evaluaciones[id] = {
                    ok: false,
                    message:
                        'Ingresa un precio válido.'
                };

                return;
            }

            this.cargando[id] = true;

            try {
                const respuesta =
                    await fetch(
                        `${this.evaluarBase}/${encodeURIComponent(codigo)}/evaluar-json`,
                        {
                            method: 'POST',
                            headers: {
                                'Accept':
                                    'application/json',

                                'Content-Type':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    this.csrf,
                            },
                            body: JSON.stringify({
                                precio:
                                    precio,

                                cliente_id:
                                    this.clienteId
                                    || null,
                            }),
                        }
                    );

                const datos =
                    await respuesta.json();

                if (
                    !respuesta.ok
                    ||
                    !datos.ok
                ) {
                    throw new Error(
                        datos.message
                        ??
                        'No se pudo validar el precio.'
                    );
                }

                this.evaluaciones[id] =
                    datos;
            } catch (error) {
                this.evaluaciones[id] = {
                    ok: false,
                    message:
                        error.message,
                };
            } finally {
                this.cargando[id] = false;
            }
        },
    }"
>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a
                href="{{ route('ventas.index') }}"
                class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Volver a ventas
            </a>

            <h1 class="text-2xl font-bold text-slate-950">
                Registrar venta directa
            </h1>

            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Selecciona los equipos, confirma el precio acordado y valida la política comercial antes de registrar.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm">
            <span class="font-black text-slate-950" x-text="seleccionados.length"></span>
            <span
                class="text-slate-500"
                x-text="seleccionados.length === 1 ? ' equipo seleccionado' : ' equipos seleccionados'"
            ></span>
        </div>
    </div>

    @if($errors->any())
        <div
            class="rounded-2xl border border-red-200 bg-red-50 p-5 text-red-700"
            role="alert"
        >
            <p class="font-bold">
                No se pudo registrar la venta.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('ventas.store') }}"
        class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
    >
        @csrf

        <div class="space-y-6">
            <x-ui.card>
                <div class="mb-5">
                    <h2 class="text-lg font-bold text-slate-950">
                        Datos del cliente
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Escribe el nombre directamente. Vincular un cliente registrado es opcional y solo sirve para autocompletar.
                    </p>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="lg:col-span-2">
                        <label
                            for="cliente_id"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Cliente registrado
                            <span class="font-normal text-slate-400">
                                · opcional
                            </span>
                        </label>

                        <select
                            id="cliente_id"
                            name="cliente_id"
                            x-model="clienteId"
                            @change="aplicarCliente()"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="">
                                No vincular · ingreso manual
                            </option>

                            @foreach($clientes as $cliente)
                                <option
                                    value="{{ $cliente->id }}"
                                    @selected(
                                        (string) old('cliente_id')
                                        ===
                                        (string) $cliente->id
                                    )
                                >
                                    {{ $cliente->nombre_completo }}
                                    {{ $cliente->telefono ? ' · '.$cliente->telefono : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label
                            for="cliente_nombre"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Nombre / Señor(es)
                        </label>

                        <input
                            id="cliente_nombre"
                            name="cliente_nombre"
                            type="text"
                            maxlength="180"
                            x-model="clienteNombre"
                            placeholder="Ej. Juan Pérez"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                            required
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Este nombre quedará congelado en la nota de venta.
                        </p>
                    </div>

                    <div>
                        <label
                            for="cliente_telefono"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Teléfono
                            <span class="font-normal text-slate-400">
                                · opcional
                            </span>
                        </label>

                        <input
                            id="cliente_telefono"
                            name="cliente_telefono"
                            type="text"
                            maxlength="50"
                            x-model="clienteTelefono"
                            placeholder="Ej. 71234567"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">
                            Equipos disponibles
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Solo aparecen unidades activas, disponibles y con precio vigente.
                        </p>
                    </div>

                    <div class="w-full sm:max-w-sm">
                        <label
                            for="buscar-equipo-venta"
                            class="sr-only"
                        >
                            Buscar equipo disponible
                        </label>

                        <input
                            id="buscar-equipo-venta"
                            type="search"
                            x-model="equipoBusqueda"
                            placeholder="Código, producto, modelo o serial..."
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse($equipos as $equipo)
                        @php
                            $textoBusqueda =
                                implode(
                                    ' ',
                                    array_filter([
                                        $equipo->codigo_interno,
                                        $equipo->serial_fabricante,
                                        $equipo->producto?->nombre,
                                        $equipo->producto?->modelo,
                                        $equipo->producto?->marca?->nombre,
                                        $equipo->almacenActual?->nombre,
                                    ])
                                );

                            $precioPublicado =
                                (float)
                                $equipo
                                    ->precioVigente
                                    ->precio_publico;
                        @endphp

                        <article
                            x-show="coincide(@js($textoBusqueda), equipoBusqueda)"
                            x-cloak
                            class="rounded-2xl border p-4 transition"
                            :class="
                                seleccionado({{ $equipo->id }})
                                    ? 'border-slate-950 bg-slate-50'
                                    : 'border-slate-200 bg-white'
                            "
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <button
                                    type="button"
                                    class="flex min-w-0 flex-1 items-start gap-3 text-left"
                                    @click="
                                        toggleEquipo(
                                            {{ $equipo->id }},
                                            {{ $precioPublicado }},
                                            @js($equipo->codigo_interno)
                                        )
                                    "
                                >
                                    <span
                                        class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-lg border text-xs font-black"
                                        :class="
                                            seleccionado({{ $equipo->id }})
                                                ? 'border-slate-950 bg-slate-950 text-white'
                                                : 'border-slate-300 bg-white text-transparent'
                                        "
                                    >
                                        ✓
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block font-bold text-slate-950">
                                            {{ $equipo->codigo_interno }}
                                        </span>

                                        <span class="mt-1 block text-sm text-slate-600">
                                            {{ $equipo->producto?->nombre ?? 'Sin producto' }}
                                            {{ $equipo->producto?->modelo ? ' · '.$equipo->producto->modelo : '' }}
                                        </span>

                                        <span class="mt-1 block text-xs text-slate-400">
                                            {{ $equipo->almacenActual?->nombre ?? 'Sin almacén' }}
                                            {{ $equipo->serial_fabricante ? ' · Serial '.$equipo->serial_fabricante : '' }}
                                        </span>
                                    </span>
                                </button>

                                <div class="shrink-0 text-left lg:text-right">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        Precio público
                                    </p>

                                    <p class="mt-1 text-xl font-black text-slate-950">
                                        Bs {{ number_format($precioPublicado, 2) }}
                                    </p>
                                </div>
                            </div>

                            <div
                                x-show="seleccionado({{ $equipo->id }})"
                                x-cloak
                                class="mt-4 grid gap-4 border-t border-slate-200 pt-4 lg:grid-cols-[minmax(0,1fr)_11rem_17rem]"
                            >
                                <div>
                                    <input
                                        type="hidden"
                                        name="equipos[{{ $equipo->id }}][equipo_id]"
                                        value="{{ $equipo->id }}"
                                        :disabled="!seleccionado({{ $equipo->id }})"
                                    >

                                    <label
                                        for="precio-venta-{{ $equipo->id }}"
                                        class="block text-sm font-semibold text-slate-700"
                                    >
                                        Precio acordado
                                    </label>

                                    <div class="mt-2 flex max-w-md items-center gap-2">
                                        <span class="text-sm font-bold text-slate-500">
                                            Bs
                                        </span>

                                        <input
                                            id="precio-venta-{{ $equipo->id }}"
                                            name="equipos[{{ $equipo->id }}][precio]"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            x-model.number="precios[{{ $equipo->id }}]"
                                            :disabled="!seleccionado({{ $equipo->id }})"
                                            @input.debounce.650ms="
                                                invalidar({{ $equipo->id }});
                                                evaluar(
                                                    {{ $equipo->id }},
                                                    @js($equipo->codigo_interno)
                                                )
                                            "
                                            class="w-full rounded-xl border-slate-300 font-bold text-slate-950 focus:border-slate-900 focus:ring-slate-900"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="condicion-venta-{{ $equipo->id }}"
                                        class="block text-sm font-semibold text-slate-700"
                                    >
                                        Condición
                                    </label>

                                    <select
                                        id="condicion-venta-{{ $equipo->id }}"
                                        name="equipos[{{ $equipo->id }}][condicion]"
                                        x-model="condiciones[{{ $equipo->id }}]"
                                        :disabled="!seleccionado({{ $equipo->id }})"
                                        class="mt-2 w-full rounded-xl border-slate-300 text-sm font-semibold focus:border-slate-900 focus:ring-slate-900"
                                    >
                                        <option value="USADO">
                                            Usado
                                        </option>

                                        <option value="NUEVO">
                                            Nuevo
                                        </option>
                                    </select>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Por defecto: usado.
                                    </p>
                                </div>

                                <div class="lg:min-w-[17rem]">
                                    <button
                                        type="button"
                                        @click="
                                            evaluar(
                                                {{ $equipo->id }},
                                                @js($equipo->codigo_interno)
                                            )
                                        "
                                        class="w-full rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-white"
                                    >
                                        Validar precio
                                    </button>

                                    <div
                                        x-show="cargando[{{ $equipo->id }}]"
                                        x-cloak
                                        class="mt-2 text-sm text-slate-500"
                                    >
                                        Validando…
                                    </div>

                                    <template
                                        x-if="
                                            !cargando[{{ $equipo->id }}]
                                            &&
                                            evaluaciones[{{ $equipo->id }}]
                                        "
                                    >
                                        <div
                                            class="mt-2 rounded-xl border p-3"
                                            :class="
                                                !evaluaciones[{{ $equipo->id }}].ok
                                                    ? 'border-red-200 bg-red-50 text-red-700'
                                                    : (
                                                        evaluaciones[{{ $equipo->id }}].permitido
                                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                                            : 'border-amber-200 bg-amber-50 text-amber-800'
                                                    )
                                            "
                                        >
                                            <p
                                                class="text-sm font-bold"
                                                x-text="
                                                    evaluaciones[{{ $equipo->id }}].message
                                                "
                                            ></p>

                                            <template
                                                x-if="
                                                    evaluaciones[{{ $equipo->id }}].ok
                                                "
                                            >
                                                <div class="mt-2 flex items-end justify-between gap-3">
                                                    <div>
                                                        <p class="text-xs opacity-70">
                                                            Descuento
                                                        </p>

                                                        <p class="font-bold">
                                                            <span x-text="
                                                                dinero(
                                                                    Math.max(
                                                                        0,
                                                                        evaluaciones[{{ $equipo->id }}].descuento
                                                                    )
                                                                )
                                                            "></span>
                                                            Bs
                                                        </p>
                                                    </div>

                                                    <div class="text-right">
                                                        <p class="text-xs opacity-70">
                                                            GANANCIA
                                                        </p>

                                                        <p class="text-lg font-black">
                                                            Bs
                                                            <span x-text="
                                                                dinero(
                                                                    evaluaciones[{{ $equipo->id }}].ganancia
                                                                )
                                                            "></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl bg-slate-50 p-8 text-center">
                            <p class="font-bold text-slate-900">
                                No hay equipos disponibles para venta.
                            </p>

                            <p class="mt-1 text-sm text-slate-500">
                                Verifica inventario, estado y precio vigente.
                            </p>
                        </div>
                    @endforelse
                </div>
            </x-ui.card>

            <x-ui.card>
                <label
                    for="observacion"
                    class="block text-sm font-semibold text-slate-700"
                >
                    Observación
                </label>

                <textarea
                    id="observacion"
                    name="observacion"
                    rows="3"
                    maxlength="1000"
                    placeholder="Dato opcional sobre la operación..."
                    class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-slate-900 focus:ring-slate-900"
                >{{ old('observacion') }}</textarea>
            </x-ui.card>
        </div>

        <aside class="xl:sticky xl:top-24 xl:self-start">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-slate-400">
                    Resumen de venta
                </p>

                <div class="mt-5 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">
                            Equipos
                        </span>

                        <strong
                            class="text-slate-950"
                            x-text="seleccionados.length"
                        ></strong>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">
                            Descuento total
                        </span>

                        <strong class="text-slate-950">
                            Bs
                            <span x-text="dinero(descuentoTotal())"></span>
                        </strong>
                    </div>

                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-sm text-slate-500">
                            Total acordado
                        </p>

                        <p class="mt-1 text-3xl font-black text-slate-950">
                            Bs
                            <span x-text="dinero(totalAcordado())"></span>
                        </p>
                    </div>
                </div>

                <div
                    x-show="
                        seleccionados.length > 0
                        &&
                        !todosEvaluados()
                    "
                    x-cloak
                    class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-700"
                >
                    Valida el precio de todos los equipos antes de continuar.
                </div>

                <div
                    x-show="hayAprobacionPendiente()"
                    x-cloak
                    class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                >
                    Hay un precio fuera de política. Al continuar se generará la solicitud de aprobación correspondiente y la venta no se cerrará todavía.</div>

                <div
                    x-show="hayBloqueo()"
                    x-cloak
                    class="mt-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    Uno de los precios no puede continuar con la política comercial actual.
                </div>

                <button
                    type="submit"
                    :disabled="
                        seleccionados.length === 0
                        ||
                        !todosEvaluados()
                        ||
                        hayBloqueo()
                    "
                    class="mt-5 w-full rounded-xl bg-slate-950 px-5 py-3 font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                    x-text="
                        hayAprobacionPendiente()
                            ? 'Solicitar aprobación'
                            : 'Registrar venta'
                    "
                ></button>

                <p class="mt-3 text-center text-xs text-slate-400">
                    La venta vuelve a validar precio, disponibilidad e inventario al guardar.
                </p>
            </div>
        </aside>
    </form>
</div>
</x-layouts.oneshop>
