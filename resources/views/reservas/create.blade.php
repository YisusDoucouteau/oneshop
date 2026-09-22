<x-layouts.oneshop title="Crear reserva | OneShop" page-title="Nueva reserva">
@php
    $equiposUi = $equipos->mapWithKeys(function ($equipo) {
        $precioPublicado = (float) ($equipo->precioVigente?->precio_publico ?? 0);

        return [
            (string) $equipo->id => [
                'id' => $equipo->id,
                'codigo' => $equipo->codigo_interno,
                'precio_publico' => $precioPublicado,
                'url_evaluar' => route(
                    'precios.equipos.evaluar-json',
                    $equipo
                ),
            ],
        ];
    })->all();

    $preciosIniciales = $equipos->mapWithKeys(function ($equipo) {
        $valor = old(
            'precios_acordados.' . $equipo->id,
            $equipo->precioVigente?->precio_publico
        );

        return [
            (string) $equipo->id =>
                $valor !== null
                    ? (float) $valor
                    : null,
        ];
    })->all();
@endphp

<div
    class="space-y-6"
    x-data="{
        clienteBusqueda: '',
        equipoBusqueda: '',
        seleccionados: @js(array_map('intval', old('equipos', []))),
        equipos: @js($equiposUi),
        precios: @js($preciosIniciales),
        ganancias: {},
        erroresGanancia: {},
        cargandoGanancia: {},
        controladores: {},

        coincide(texto, busqueda) {
            return texto
                .toLowerCase()
                .includes(
                    busqueda
                        .trim()
                        .toLowerCase()
                );
        },

        dinero(valor) {
            return new Intl.NumberFormat(
                'es-BO',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(Number(valor ?? 0));
        },

        seleccionado(id) {
            return this.seleccionados.includes(
                Number(id)
            );
        },

        async alternarEquipo(id) {
            if (!this.seleccionado(id)) {
                if (this.controladores[id]) {
                    this.controladores[id].abort();
                }

                delete this.ganancias[id];
                delete this.erroresGanancia[id];
                return;
            }

            await this.evaluarGanancia(id);
        },

        async evaluarGanancia(id) {
            if (!this.seleccionado(id)) {
                return;
            }

            const equipo = this.equipos[id];
            const valor = Number(
                this.precios[id]
            );

            if (!equipo || !valor || valor <= 0) {
                delete this.ganancias[id];
                this.erroresGanancia[id] =
                    'Ingresa un precio válido.';
                return;
            }

            if (
                valor >
                Number(equipo.precio_publico)
            ) {
                delete this.ganancias[id];
                this.erroresGanancia[id] =
                    'El precio acordado no puede superar el publicado.';
                return;
            }

            if (this.controladores[id]) {
                this.controladores[id].abort();
            }

            this.controladores[id] =
                new AbortController();

            this.cargandoGanancia[id] = true;
            delete this.erroresGanancia[id];

            try {
                const respuesta = await fetch(
                    equipo.url_evaluar,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type':
                                'application/json',
                            'Accept':
                                'application/json',
                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name=csrf-token]'
                                    )
                                    .getAttribute(
                                        'content'
                                    ),
                        },
                        body: JSON.stringify({
                            precio_rebaja:
                                valor,
                        }),
                        signal:
                            this.controladores[id]
                                .signal,
                    }
                );

                const datos =
                    await respuesta.json();

                if (
                    !respuesta.ok
                    || !datos.ok
                ) {
                    throw new Error(
                        datos.message
                        ?? 'No se pudo calcular la ganancia.'
                    );
                }

                this.ganancias[id] =
                    datos.ganancia;
            } catch (error) {
                if (
                    error.name
                    !== 'AbortError'
                ) {
                    delete this.ganancias[id];
                    this.erroresGanancia[id] =
                        error.message;
                }
            } finally {
                this.cargandoGanancia[id] =
                    false;
            }
        },

        init() {
            this.$nextTick(() => {
                this.seleccionados
                    .forEach(
                        id =>
                            this.evaluarGanancia(
                                id
                            )
                    );
            });
        }
    }"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a
                href="{{ route('reservas.index') }}"
                class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Volver a reservas
            </a>

            <h1 class="text-2xl font-bold text-slate-900">
                Registrar nueva reserva
            </h1>

            <p class="mt-1 max-w-2xl text-sm text-slate-500">
                Selecciona el cliente, acuerda el precio y define hasta cuándo se separarán los equipos.
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm">
            <span class="font-bold text-slate-900" x-text="seleccionados.length"></span>
            <span
                class="text-slate-500"
                x-text="seleccionados.length === 1 ? ' equipo seleccionado' : ' equipos seleccionados'"
            ></span>
        </div>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700" role="alert">
            <p class="font-semibold">
                Revisa la información antes de continuar.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('reservas.store') }}"
        class="space-y-6"
    >
        @csrf

        <div class="grid gap-6 xl:grid-cols-[1fr_21rem]">
            <div class="space-y-6">
                <x-ui.card>
                    <div class="mb-4">
                        <h2 class="font-bold text-slate-900">
                            Cliente
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Busca por nombre o teléfono.
                        </p>
                    </div>

                    <input
                        type="search"
                        x-model="clienteBusqueda"
                        placeholder="Buscar cliente..."
                        class="w-full rounded-xl border-slate-300"
                        autocomplete="off"
                    >

                    <select
                        name="cliente_id"
                        class="mt-3 w-full rounded-xl border-slate-300"
                        required
                    >
                        <option value="">
                            Seleccione un cliente
                        </option>

                        @foreach($clientes as $cliente)
                            <option
                                value="{{ $cliente->id }}"
                                x-show="coincide(@js($cliente->nombre_completo.' '.$cliente->telefono), clienteBusqueda)"
                                {{ (string) old('cliente_id') === (string) $cliente->id ? 'selected' : '' }}
                            >
                                {{ $cliente->nombre_completo }}
                                {{ $cliente->telefono ? ' · '.$cliente->telefono : '' }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.card>

                <x-ui.card>
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 class="font-bold text-slate-900">
                                Equipos
                            </h2>
                            <p class="mt-1 text-sm text-slate-500">
                                Selecciona una unidad y ajusta el precio solo si hubo negociación.
                            </p>
                        </div>

                        <div class="text-sm font-semibold text-slate-500">
                            <span x-text="seleccionados.length"></span>
                            seleccionados
                        </div>
                    </div>

                    <input
                        type="search"
                        x-model="equipoBusqueda"
                        placeholder="Código, modelo o serie..."
                        class="w-full rounded-xl border-slate-300"
                        autocomplete="off"
                    >

                    <div class="mt-4 space-y-3">
                        @forelse($equipos as $equipo)
                            @php
                                $textoBusqueda = implode(
                                    ' ',
                                    array_filter([
                                        $equipo->codigo_interno,
                                        $equipo->serial_fabricante,
                                        $equipo->producto?->nombre,
                                        $equipo->producto?->modelo,
                                    ])
                                );

                                $precioPublicado =
                                    (float)
                                    ($equipo->precioVigente?->precio_publico ?? 0);
                            @endphp

                            <div
                                x-show="coincide(@js($textoBusqueda), equipoBusqueda)"
                                class="rounded-2xl border border-slate-200 bg-white p-4"
                                :class="seleccionado({{ $equipo->id }}) ? 'ring-2 ring-oneshop-primary/20 border-oneshop-primary' : ''"
                            >
                                <div class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        name="equipos[]"
                                        value="{{ $equipo->id }}"
                                        x-model.number="seleccionados"
                                        @change="alternarEquipo({{ $equipo->id }})"
                                        class="mt-1 rounded border-slate-300 text-oneshop-primary focus:ring-oneshop-primary"
                                    >

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="font-bold text-slate-900">
                                                    {{ $equipo->codigo_interno }}
                                                </p>

                                                <p class="mt-1 text-sm text-slate-600">
                                                    {{ $equipo->producto?->nombre ?? 'Equipo' }}
                                                    {{ $equipo->producto?->modelo }}
                                                </p>

                                                @if($equipo->serial_fabricante)
                                                    <p class="mt-1 text-xs text-slate-400">
                                                        Serie:
                                                        {{ $equipo->serial_fabricante }}
                                                    </p>
                                                @endif
                                            </div>

                                            <div class="text-left sm:text-right">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Precio publicado
                                                </p>

                                                <p class="font-bold text-slate-900">
                                                    Bs {{ number_format($precioPublicado, 2) }}
                                                </p>
                                            </div>
                                        </div>

                                        <div
                                            x-show="seleccionado({{ $equipo->id }})"
                                            x-cloak
                                            class="mt-4 grid gap-3 border-t border-slate-100 pt-4 md:grid-cols-[1fr_auto]"
                                        >
                                            <div>
                                                <label class="text-sm font-semibold text-slate-700">
                                                    Precio acordado
                                                </label>

                                                <div class="relative mt-1">
                                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-semibold text-slate-500">
                                                        Bs
                                                    </span>

                                                    <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0.01"
                                                        max="{{ number_format($precioPublicado, 2, '.', '') }}"
                                                        name="precios_acordados[{{ $equipo->id }}]"
                                                        x-model="precios[{{ $equipo->id }}]"
                                                        @input.debounce.350ms="evaluarGanancia({{ $equipo->id }})"
                                                        :disabled="!seleccionado({{ $equipo->id }})"
                                                        class="w-full rounded-xl border-slate-300 pl-10"
                                                        required
                                                    >
                                                </div>

                                                <p class="mt-1 text-xs text-slate-400">
                                                    Si no hubo rebaja, conserva el precio publicado.
                                                </p>
                                            </div>

                                            <div class="min-w-[11rem] rounded-xl bg-slate-50 px-4 py-3 text-center">
                                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                                    Ganancia
                                                </p>

                                                <p
                                                    x-show="cargandoGanancia[{{ $equipo->id }}]"
                                                    class="mt-1 text-sm font-semibold text-slate-500"
                                                >
                                                    Calculando…
                                                </p>

                                                <p
                                                    x-show="!cargandoGanancia[{{ $equipo->id }}] && ganancias[{{ $equipo->id }}] !== undefined"
                                                    x-cloak
                                                    class="mt-1 text-2xl font-black"
                                                    :class="Number(ganancias[{{ $equipo->id }}] ?? 0) >= 0 ? 'text-emerald-600' : 'text-red-600'"
                                                >
                                                    Bs
                                                    <span x-text="dinero(ganancias[{{ $equipo->id }}])"></span>
                                                </p>

                                                <p
                                                    x-show="erroresGanancia[{{ $equipo->id }}]"
                                                    x-cloak
                                                    class="mt-1 max-w-[12rem] text-xs font-medium text-red-600"
                                                    x-text="erroresGanancia[{{ $equipo->id }}]"
                                                ></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 px-6 py-10 text-center">
                                <p class="font-semibold text-slate-700">
                                    No hay equipos disponibles.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card>
                    <h2 class="font-bold text-slate-900">
                        Vigencia
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Define hasta cuándo se mantendrá separada la reserva.
                    </p>

                    <label
                        for="fecha_expiracion"
                        class="mt-4 block text-sm font-semibold text-slate-700"
                    >
                        Reservar hasta
                    </label>

                    <input
                        id="fecha_expiracion"
                        type="datetime-local"
                        name="fecha_expiracion"
                        value="{{ old('fecha_expiracion', now()->addDay()->format('Y-m-d\TH:i')) }}"
                        min="{{ now()->format('Y-m-d\TH:i') }}"
                        max="{{ now()->addDays($diasMaximosReserva)->format('Y-m-d\TH:i') }}"
                        class="mt-2 w-full rounded-xl border-slate-300"
                        required
                    >

                    <p class="mt-2 text-xs text-slate-500">
                        Plazo máximo estándar:
                        {{ $diasMaximosReserva }}
                        {{ $diasMaximosReserva === 1 ? 'día' : 'días' }}.
                    </p>

                    <label
                        for="observacion"
                        class="mt-5 block text-sm font-semibold text-slate-700"
                    >
                        Observación
                    </label>

                    <textarea
                        id="observacion"
                        name="observacion"
                        rows="3"
                        maxlength="1000"
                        placeholder="Opcional"
                        class="mt-2 w-full rounded-xl border-slate-300"
                    >{{ old('observacion') }}</textarea>
                </x-ui.card>

                <div class="rounded-2xl bg-slate-950 p-5 text-white shadow-lg">
                    <p class="text-sm text-slate-300">
                        Equipos a reservar
                    </p>

                    <p
                        class="mt-1 text-3xl font-black"
                        x-text="seleccionados.length"
                    ></p>

                    <button
                        type="submit"
                        class="mt-5 w-full rounded-xl bg-oneshop-primary px-5 py-3 font-semibold text-white transition hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="seleccionados.length === 0"
                    >
                        Confirmar reserva
                    </button>

                    <a
                        href="{{ route('reservas.index') }}"
                        class="mt-3 block w-full rounded-xl border border-slate-700 px-5 py-3 text-center font-medium text-slate-200 hover:bg-slate-900"
                    >
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
</x-layouts.oneshop>
