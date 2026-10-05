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
        secuenciaEvaluacion: {},
        motivosExcepcion: {},
        solicitandoExcepcion: {},
        excepcionesEnviadas: {},
        erroresExcepcion: {},
        csrf: @js(csrf_token()),
        evaluarBase: @js(url('/ventas/equipos')),
        solicitarBase: @js(url('/ventas/equipos')),

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

                this.secuenciaEvaluacion[id] =
                    (this.secuenciaEvaluacion[id] ?? 0) + 1;

                delete this.evaluaciones[id];
                delete this.cargando[id];
                delete this.excepcionesEnviadas[id];
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

        totalPublicado() {
            return this.seleccionados.reduce(
                (total, id) =>
                    total
                    +
                    Number(
                        this.publicados[id]
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
            id = Number(id);

            /*
             * Cada pulsación invalida inmediatamente el resultado visible y
             * cualquier petición AJAX anterior. Así una respuesta lenta para
             * Una respuesta antigua nunca puede sobrescribir la evaluación más reciente.
             */
            this.secuenciaEvaluacion[id] =
                (this.secuenciaEvaluacion[id] ?? 0) + 1;

            delete this.evaluaciones[id];
            this.cargando[id] = false;

            /*
             * Una excepción enviada corresponde al precio exacto que se pidió.
             * Si el vendedor cambia el monto, la pantalla debe volver a evaluar
             * el nuevo precio y no mostrar una solicitud anterior como vigente.
             */
            delete this.excepcionesEnviadas[id];
            delete this.erroresExcepcion[id];
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

        async solicitarExcepcion(id, codigo) {
            id = Number(id);

            const motivo = String(
                this.motivosExcepcion[id] ?? ''
            ).trim();

            if (motivo.length < 5) {
                this.erroresExcepcion[id] =
                    'Explica brevemente por qué necesitas esta excepción.';
                return;
            }

            this.solicitandoExcepcion[id] = true;
            this.erroresExcepcion[id] = '';

            try {
                const respuesta = await fetch(
                    `${this.solicitarBase}/${encodeURIComponent(codigo)}/solicitar-autorizacion`,
                    {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({
                            precio_propuesto:
                                Number(this.precios[id] ?? 0),
                            motivo,
                        }),
                    }
                );

                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.ok) {
                    throw new Error(
                        datos.message
                        ?? 'No se pudo enviar la solicitud.'
                    );
                }

                this.excepcionesEnviadas[id] =
                    datos.message
                    ?? 'Solicitud enviada a administración.';
            } catch (error) {
                this.erroresExcepcion[id] =
                    error.message;
            } finally {
                this.solicitandoExcepcion[id] = false;
            }
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

            /*
             * Token monotónico por equipo. Solo la petición más reciente puede
             * pintar el resultado. Esto evita carreras al escribir 4 -> 44 ->
             * 444 -> 4444 rápidamente.
             */
            const secuencia =
                (this.secuenciaEvaluacion[id] ?? 0) + 1;

            this.secuenciaEvaluacion[id] = secuencia;

            if (!precio || precio <= 0) {
                this.evaluaciones[id] = {
                    ok: false,
                    message:
                        'Ingresa un precio válido.'
                };

                this.cargando[id] = false;
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

                if (this.secuenciaEvaluacion[id] !== secuencia) {
                    return;
                }

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
                if (this.secuenciaEvaluacion[id] !== secuencia) {
                    return;
                }

                this.evaluaciones[id] = {
                    ok: false,
                    message:
                        error.message,
                };
            } finally {
                if (this.secuenciaEvaluacion[id] === secuencia) {
                    this.cargando[id] = false;
                }
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

            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Busca el equipo, acuerda el precio con el cliente y registra la venta. OneShop valida la negociación automáticamente.
            </p>
        </div>

        <div class="rounded-xl border border-blue-100 bg-white px-4 py-3 text-sm shadow-sm">
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
                        Selecciona un cliente registrado para autocompletar o escribe sus datos directamente.
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
                            class="input-oneshop mt-2 w-full text-sm"
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
                            class="input-oneshop mt-2 w-full text-sm"
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
                            class="input-oneshop mt-2 w-full text-sm"
                        >
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-950">
                            Buscar y agregar equipos
                        </h2>

                        <p class="mt-1 text-sm text-slate-500">
                            Solo aparecen equipos listos para venta. Puedes buscar por código, producto, modelo o número de serie.
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
                            placeholder="Buscar equipo..."
                            class="input-oneshop w-full text-sm"
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
                            class="rounded-2xl border p-4 transition duration-200"
                            :class="
                                seleccionado({{ $equipo->id }})
                                    ? 'border-blue-300 bg-blue-50/40 shadow-sm'
                                    : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-slate-50/60'
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
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-slate-300 bg-white text-transparent'
                                        "
                                    >
                                        ✓
                                    </span>

                                    <span class="min-w-0">
                                        <span class="flex flex-wrap items-center gap-2">
                                            <span class="block font-bold text-slate-950">
                                                {{ $equipo->codigo_interno }}
                                            </span>

                                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-emerald-700">
                                                Disponible
                                            </span>
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

                                    <p
                                        class="mt-2 text-xs font-bold"
                                        :class="
                                            seleccionado({{ $equipo->id }})
                                                ? 'text-blue-700'
                                                : 'text-slate-500'
                                        "
                                        x-text="
                                            seleccionado({{ $equipo->id }})
                                                ? 'Agregado a la venta'
                                                : 'Agregar'
                                        "
                                    ></p>
                                </div>
                            </div>

                            <div
                                x-show="seleccionado({{ $equipo->id }})"
                                x-cloak
                                class="mt-4 grid gap-4 border-t border-blue-100 pt-4 lg:grid-cols-[minmax(0,1fr)_10rem_minmax(18rem,0.9fr)]"
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
                                            @input="invalidar({{ $equipo->id }})"
                                            @input.debounce.650ms="
                                                evaluar(
                                                    {{ $equipo->id }},
                                                    @js($equipo->codigo_interno)
                                                )
                                            "
                                            class="input-oneshop w-full font-bold text-slate-950"
                                        >
                                    </div>
                                </div>

                                <div>
                                    <label
                                        for="condicion-{{ $equipo->id }}"
                                        class="block text-sm font-semibold text-slate-700"
                                    >
                                        Condición de venta
                                    </label>

                                    <select
                                        id="condicion-{{ $equipo->id }}"
                                        name="equipos[{{ $equipo->id }}][condicion]"
                                        x-model="condiciones[{{ $equipo->id }}]"
                                        :disabled="!seleccionado({{ $equipo->id }})"
                                        class="input-oneshop mt-2 w-full"
                                    >
                                        <option value="USADO">Usado</option>
                                        <option value="NUEVO">Nuevo</option>
                                    </select>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Por defecto se registra como usado. Cámbialo solo si el equipo es nuevo.
                                    </p>
                                </div>

                                <div class="lg:min-w-[18rem]">
                                    <p class="text-sm font-semibold text-slate-700">
                                        Validación comercial
                                    </p>

                                    <div
                                        x-show="cargando[{{ $equipo->id }}]"
                                        x-cloak
                                        class="mt-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-3 text-sm text-blue-700"
                                    >
                                        Validando automáticamente…
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
                                                            : (
                                                                evaluaciones[{{ $equipo->id }}].requiere_aprobacion
                                                                    ? 'border-amber-200 bg-amber-50 text-amber-800'
                                                                    : 'border-red-200 bg-red-50 text-red-700'
                                                            )
                                                    )
                                            "
                                        >
                                            <p
                                                class="text-sm font-black"
                                                x-text="
                                                    !evaluaciones[{{ $equipo->id }}].ok
                                                        ? 'No se puede continuar'
                                                        : (
                                                            evaluaciones[{{ $equipo->id }}].permitido
                                                                ? 'Precio permitido'
                                                                : (
                                                                    evaluaciones[{{ $equipo->id }}].requiere_aprobacion
                                                                        ? 'Fuera del rango normal'
                                                                        : 'Precio bloqueado'
                                                                )
                                                        )
                                                "
                                            ></p>

                                            <p
                                                class="mt-1 text-xs opacity-80"
                                                x-text="
                                                    evaluaciones[{{ $equipo->id }}].estado === 'NO_RECOMENDADA'
                                                        ? 'Este precio genera una pérdida y no puede registrarse.'
                                                        : evaluaciones[{{ $equipo->id }}].message
                                                "
                                            ></p>

                                            <template
                                                x-if="
                                                    evaluaciones[{{ $equipo->id }}].ok
                                                "
                                            >
                                                <div class="mt-3 grid grid-cols-2 gap-3 border-t border-current/10 pt-3">
                                                    <div>
                                                        <p class="text-xs opacity-70">
                                                            Descuento
                                                        </p>

                                                        <p class="font-bold">
                                                            Bs
                                                            <span x-text="
                                                                dinero(
                                                                    Math.max(
                                                                        0,
                                                                        evaluaciones[{{ $equipo->id }}].descuento
                                                                    )
                                                                )
                                                            "></span>
                                                        </p>
                                                    </div>

                                                    <div class="text-right">
                                                        <p
                                                            class="text-xs opacity-70"
                                                            x-text="
                                                                Number(evaluaciones[{{ $equipo->id }}].ganancia) < 0
                                                                    ? 'Pérdida estimada'
                                                                    : 'Ganancia estimada'
                                                            "
                                                        ></p>

                                                        <p class="font-black">
                                                            Bs
                                                            <span x-text="
                                                                dinero(
                                                                    Math.abs(
                                                                        evaluaciones[{{ $equipo->id }}].ganancia
                                                                    )
                                                                )
                                                            "></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <p class="mt-2 text-xs text-slate-400">
                                        El precio se valida automáticamente mientras escribes.
                                    </p>

                                    <div
                                        x-show="
                                            evaluaciones[{{ $equipo->id }}]
                                            && evaluaciones[{{ $equipo->id }}].ok
                                            && !evaluaciones[{{ $equipo->id }}].permitido
                                            && evaluaciones[{{ $equipo->id }}].requiere_aprobacion
                                        "
                                        x-cloak
                                        class="mt-3 rounded-xl border border-amber-200 bg-white p-3"
                                    >
                                        <template x-if="!excepcionesEnviadas[{{ $equipo->id }}]">
                                            <div>
                                                <label class="block text-xs font-bold text-slate-700">
                                                    Motivo de la excepción
                                                </label>

                                                <textarea
                                                    rows="2"
                                                    maxlength="500"
                                                    x-model="motivosExcepcion[{{ $equipo->id }}]"
                                                    placeholder="Ej. Cliente confirma la compra hoy si se mantiene este precio."
                                                    class="input-oneshop mt-2 w-full text-sm"
                                                ></textarea>

                                                <p
                                                    x-show="erroresExcepcion[{{ $equipo->id }}]"
                                                    x-text="erroresExcepcion[{{ $equipo->id }}]"
                                                    class="mt-1 text-xs font-semibold text-red-600"
                                                ></p>

                                                <button
                                                    type="button"
                                                    @click="solicitarExcepcion({{ $equipo->id }}, @js($equipo->codigo_interno))"
                                                    :disabled="solicitandoExcepcion[{{ $equipo->id }}]"
                                                    class="mt-2 w-full rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-bold text-amber-900 hover:bg-amber-100 disabled:cursor-wait disabled:opacity-60"
                                                    x-text="
                                                        solicitandoExcepcion[{{ $equipo->id }}]
                                                            ? 'Enviando…'
                                                            : 'Solicitar excepción a administración'
                                                    "
                                                ></button>
                                            </div>
                                        </template>

                                        <template x-if="excepcionesEnviadas[{{ $equipo->id }}]">
                                            <div class="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">
                                                <p class="font-black">
                                                    Solicitud enviada
                                                </p>
                                                <p
                                                    class="mt-1 text-xs"
                                                    x-text="excepcionesEnviadas[{{ $equipo->id }}]"
                                                ></p>
                                            </div>
                                        </template>
                                    </div>
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
                    class="input-oneshop mt-2 w-full text-sm"
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
                            Total publicado
                        </span>

                        <strong class="text-slate-950">
                            Bs
                            <span x-text="dinero(totalPublicado())"></span>
                        </strong>
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
                    OneShop está validando la negociación. Espera un momento antes de continuar.
                </div>

                <div
                    x-show="hayAprobacionPendiente()"
                    x-cloak
                    class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800"
                >
                    Hay una negociación fuera del rango normal. La solicitud solo se envía desde el equipo afectado y debe incluir un motivo.
                </div>

                <div
                    x-show="hayBloqueo()"
                    x-cloak
                    class="mt-5 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                >
                    Uno de los precios está bloqueado. Puede generar pérdida o incumplir una regla comercial y debe corregirse antes de continuar.
                </div>

                <button
                    type="submit"
                    :disabled="
                        seleccionados.length === 0
                        ||
                        !todosEvaluados()
                        ||
                        hayBloqueo()
                        ||
                        hayAprobacionPendiente()
                    "
                    class="btn-primary mt-5 w-full py-3 font-bold"
                    :class="
                        hayBloqueo()
                            ? '!border-slate-200 !bg-slate-100 !text-slate-500 cursor-not-allowed'
                            : (
                                hayAprobacionPendiente()
                                    ? '!border-amber-200 !bg-amber-50 !text-amber-700 cursor-not-allowed'
                                    : ''
                            )
                    "
                    x-text="
                        seleccionados.length === 0
                            ? 'Selecciona un equipo'
                            : (
                                !todosEvaluados()
                                    ? 'Validando negociación…'
                                    : (
                                        hayBloqueo()
                                            ? 'Corrige el precio bloqueado'
                                            : (
                                                hayAprobacionPendiente()
                                                    ? 'Resuelve la excepción pendiente'
                                                    : 'Registrar venta'
                                            )
                                    )
                            )
                    "
                ></button>

                <p class="mt-3 text-center text-xs text-slate-400">
                    Al confirmar, OneShop vuelve a validar precio, disponibilidad e inventario.
                </p>
            </div>
        </aside>
    </form>
</div>
</x-layouts.oneshop>
