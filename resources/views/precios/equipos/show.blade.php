<x-layouts.oneshop
    title="Gestión de precio | OneShop"
    page-title="Gestión de precio"
>

<div
    class="space-y-6"
    x-data="{
        precio: @js(
            old(
                'precio_rebaja',
                $formularioRebaja['precio_rebaja']
                    ?? $equipo->precioVigente?->precio_publico
                    ?? ''
            )
        ),
        cargando: false,
        resultado: @js(
            $evaluacion
                ? [
                    'ok' => true,
                    'precio_publicado' => $evaluacion['precio_publicado'],
                    'precio_rebaja' => $evaluacion['precio_rebaja'],
                    'costo_actualizado' => $evaluacion['costo_actualizado'],
                    'tipo_cambio' => $evaluacion['tipo_cambio'],
                    'moneda_origen' => $evaluacion['moneda_origen'],
                    'ganancia' => $evaluacion['ganancia'],
                    'margen_total' => $esAdministrador
                        ? $evaluacion['margen_total']
                        : null,
                    'reparto' => $esAdministrador
                        ? $evaluacion['reparto']
                        : null,
                ]
                : null
        ),
        error: @js($errorEvaluacion),
        controlador: null,

        dinero(valor) {
            return new Intl.NumberFormat(
                'es-BO',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }
            ).format(Number(valor ?? 0));
        },

        async evaluar() {
            const valor = Number(this.precio);

            if (!valor || valor <= 0) {
                this.resultado = null;
                this.error = null;
                return;
            }

            if (this.controlador) {
                this.controlador.abort();
            }

            this.controlador =
                new AbortController();

            this.cargando = true;
            this.error = null;

            try {
                const respuesta = await fetch(
                    @js(route('precios.equipos.evaluar-json', $equipo)),
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                        },
                        body: JSON.stringify({
                            precio_rebaja: valor,
                        }),
                        signal: this.controlador.signal,
                    }
                );

                const datos =
                    await respuesta.json();

                if (!respuesta.ok || !datos.ok) {
                    throw new Error(
                        datos.message
                        ?? 'No se pudo evaluar la rebaja.'
                    );
                }

                this.resultado = datos;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    this.resultado = null;
                    this.error = error.message;
                }
            } finally {
                this.cargando = false;
            }
        }
    }"
>

    {{-- Encabezado simple --}}
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a
                href="{{ route('inventario.show', $equipo) }}"
                class="text-sm font-medium text-blue-600 hover:text-blue-700"
            >
                ← Volver al equipo
            </a>

            <h1 class="mt-2 text-2xl font-bold text-slate-950">
                {{ $equipo->producto?->nombre ?? 'Equipo' }}
                {{ $equipo->producto?->modelo }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $equipo->codigo_interno }}
            </p>
        </div>

        <div class="flex flex-wrap gap-3">
            @if($equipo->precioVigente)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Publicado
                    </p>
                    <p class="mt-1 text-lg font-bold text-slate-950">
                        Bs {{ number_format($equipo->precioVigente->precio_publico, 2) }}
                    </p>
                </div>
            @endif

            @if($tipoCambioVigente)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        TC vigente
                    </p>
                    <p class="mt-1 text-lg font-bold text-slate-950">
                        Bs {{ number_format($tipoCambioVigente->valor, 2) }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Zona principal: mínima carga visual --}}
    <div class="grid gap-6 xl:grid-cols-[1fr_1.15fr]">

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="font-semibold text-slate-950">
                    Evaluar rebaja
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Escribe el precio que solicita el cliente.
                </p>
            </div>

            <div class="p-6">
                @if($costoComercial)
                    <label class="text-sm font-medium text-slate-700">
                        Precio de rebaja
                    </label>

                    <div class="relative mt-2">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-500">
                            Bs
                        </span>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            x-model="precio"
                            @input.debounce.350ms="evaluar()"
                            class="w-full rounded-2xl border-slate-300 py-4 pl-11 pr-4 text-2xl font-bold"
                            placeholder="0.00"
                            autofocus
                        >
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Costo actualizado
                            </p>
                            <p class="mt-1 font-bold text-slate-900">
                                Bs {{ number_format($costoComercial['costo_total'], 2) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Tipo de cambio
                            </p>
                            <p class="mt-1 font-bold text-slate-900">
                                @if($costoComercial['usa_tipo_cambio'])
                                    Bs {{ number_format($costoComercial['tipo_cambio'], 2) }}
                                @else
                                    No aplica
                                @endif
                            </p>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-800">
                        <p class="font-semibold">
                            No se puede evaluar todavía.
                        </p>
                        <p class="mt-1">
                            {{ $errorCostoComercial }}
                        </p>
                    </div>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex min-h-[290px] items-center justify-center p-7">

                <div
                    x-show="cargando"
                    class="text-center"
                >
                    <p class="text-sm font-medium text-slate-500">
                        Calculando…
                    </p>
                </div>

                <div
                    x-show="!cargando && error"
                    x-cloak
                    class="max-w-md text-center"
                >
                    <p class="font-semibold text-red-700">
                        No se pudo evaluar
                    </p>
                    <p
                        class="mt-2 text-sm text-red-600"
                        x-text="error"
                    ></p>
                </div>

                <div
                    x-show="!cargando && !error && !resultado"
                    x-cloak
                    class="max-w-md text-center"
                >
                    <p class="text-sm font-medium text-slate-400">
                        La ganancia aparecerá aquí mientras escribes.
                    </p>
                </div>

                <div
                    x-show="!cargando && !error && resultado"
                    x-cloak
                    class="w-full"
                >
                    <div class="text-center">
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">
                            Ganancia
                        </p>

                        <p
                            class="mt-3 text-6xl font-black"
                            :class="
                                Number(resultado?.ganancia ?? 0) > 0
                                    ? 'text-emerald-600'
                                    : (
                                        Number(resultado?.ganancia ?? 0) < 0
                                            ? 'text-red-600'
                                            : 'text-amber-600'
                                    )
                            "
                        >
                            Bs <span x-text="dinero(resultado?.ganancia)"></span>
                        </p>

                        <p class="mx-auto mt-4 max-w-md text-sm text-slate-500">
                            Referencia principal para decidir si la rebaja mantiene una venta conveniente.
                        </p>
                    </div>

                    @if($esAdministrador)
                        <details
                            class="mt-7 border-t border-slate-100 pt-5"
                            x-show="resultado?.reparto"
                            data-testid="reparto-administrativo"
                        >
                            <summary class="cursor-pointer select-none text-center text-sm font-semibold text-slate-600">
                                Ver detalle administrativo
                            </summary>

                            <div class="mt-5">
                                <p class="text-center text-sm text-slate-500">
                                    Margen total:
                                    <strong class="text-slate-900">
                                        Bs <span x-text="dinero(resultado?.margen_total)"></span>
                                    </strong>
                                </p>

                                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Hugo
                                        </p>
                                        <p class="mt-1 text-xl font-bold text-slate-900">
                                            Bs <span x-text="dinero(resultado?.reparto?.hugo)"></span>
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Daniel
                                        </p>
                                        <p class="mt-1 text-xl font-bold text-slate-900">
                                            Bs <span x-text="dinero(resultado?.reparto?.daniel)"></span>
                                        </p>
                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-4 text-center">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Tienda
                                        </p>
                                        <p class="mt-1 text-xl font-bold text-slate-900">
                                            Bs <span x-text="dinero(resultado?.reparto?.tienda)"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </section>
    </div>

    {{-- Configuración global: compacta y solo admin --}}
    @if(
        $esAdministrador
        && $monedaOrigen
        && $monedaOrigen->codigo !== 'BOB'
    )
        <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer select-none px-6 py-5">
                <span class="font-semibold text-slate-950">
                    Tipo de cambio comercial
                </span>
                <span class="ml-2 text-sm text-slate-500">
                    Global para {{ $monedaOrigen->codigo }}
                </span>
            </summary>

            <div class="border-t border-slate-100 px-6 py-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="text-sm text-slate-500">
                            Este valor se utiliza en todos los equipos comprados en {{ $monedaOrigen->codigo }}.
                        </p>

                        @if($tipoCambioVigente)
                            <p class="mt-2 text-sm font-medium text-slate-700">
                                Vigente:
                                Bs {{ number_format($tipoCambioVigente->valor, 2) }}
                                · {{ $tipoCambioVigente->fecha_vigencia?->format('d/m/Y H:i') }}
                            </p>
                        @endif
                    </div>

                    <form
                        method="POST"
                        action="{{ route('precios.tipo-cambio.store') }}"
                        class="flex gap-3"
                    >
                        @csrf

                        <input
                            type="hidden"
                            name="moneda_origen_id"
                            value="{{ $monedaOrigen->id }}"
                        >

                        <input
                            type="hidden"
                            name="return_to"
                            value="{{ url()->current() }}"
                        >

                        <input
                            type="number"
                            step="0.000001"
                            min="0.000001"
                            name="valor_tipo_cambio"
                            value="{{ $tipoCambioVigente?->valor }}"
                            placeholder="Ej. 12.00"
                            class="w-40 rounded-xl border-slate-300"
                            required
                        >

                        <button
                            type="submit"
                            class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Actualizar
                        </button>
                    </form>
                </div>
            </div>
        </details>
    @endif

    {{-- Administración secundaria: colapsada para no saturar --}}
    @if($esAdministrador)
        <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <summary class="cursor-pointer select-none px-6 py-5">
                <span class="font-semibold text-slate-950">
                    Administración del precio
                </span>
                <span class="ml-2 text-sm text-slate-500">
                    Precio publicado, mínimo e historial
                </span>
            </summary>

            <div class="border-t border-slate-100 p-6">
                <form
                    method="POST"
                    action="{{ route('precios.equipos.store', $equipo) }}"
                    class="grid gap-4 lg:grid-cols-4"
                >
                    @csrf

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Precio recomendado
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="precio_sugerido"
                            value="{{ old(
                                'precio_sugerido',
                                $equipo->precioVigente?->precio_sugerido ?? ''
                            ) }}"
                            class="mt-1 w-full rounded-xl border-slate-300"
                            required
                        >
                        <p class="mt-1 text-xs text-slate-400">
                            Por ahora es una referencia administrativa; la sugerencia automática se implementará después.
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Precio publicado
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="precio_publico"
                            value="{{ old(
                                'precio_publico',
                                $equipo->precioVigente?->precio_publico ?? ''
                            ) }}"
                            class="mt-1 w-full rounded-xl border-slate-300"
                            required
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Mínimo autorizado
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="precio_minimo_autorizado"
                            value="{{ old(
                                'precio_minimo_autorizado',
                                $equipo->precioVigente?->precio_minimo_autorizado ?? ''
                            ) }}"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="w-full rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Guardar precio
                        </button>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="text-sm font-medium text-slate-700">
                            Observación
                        </label>
                        <textarea
                            name="observacion"
                            rows="2"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >{{ old('observacion') }}</textarea>
                    </div>
                </form>

                <div class="mt-8 border-t border-slate-100 pt-6">
                    <h3 class="font-semibold text-slate-900">
                        Historial de precios
                    </h3>

                    @if($historial->isEmpty())
                        <p class="mt-3 text-sm text-slate-500">
                            No existen precios registrados.
                        </p>
                    @else
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-sm">
                                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <tr>
                                        <th class="px-4 py-3">Desde</th>
                                        <th class="px-4 py-3">Publicado</th>
                                        <th class="px-4 py-3">Costo usado</th>
                                        <th class="px-4 py-3">Mínimo</th>
                                        <th class="px-4 py-3">Estado</th>
                                        <th class="px-4 py-3">Registrado por</th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100">
                                    @foreach($historial as $precio)
                                        <tr>
                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $precio->vigente_desde?->format('d/m/Y H:i') ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 font-semibold text-slate-950">
                                                Bs {{ number_format($precio->precio_publico, 2) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-600">
                                                Bs {{ number_format($precio->costo_total_snapshot, 2) }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $precio->precio_minimo_autorizado !== null
                                                    ? 'Bs ' . number_format($precio->precio_minimo_autorizado, 2)
                                                    : '—' }}
                                            </td>

                                            <td class="px-4 py-3">
                                                {{ $precio->vigente ? 'Vigente' : 'Histórico' }}
                                            </td>

                                            <td class="px-4 py-3 text-slate-600">
                                                {{ $precio->aprobadoPor?->name ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </details>
    @endif

</div>

</x-layouts.oneshop>
