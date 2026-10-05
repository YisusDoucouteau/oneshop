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
                    ?? ($sugerenciaPrecio['precio_sugerido'] ?? null)
                    ?? ''
            )
        ),
        puedeEvaluar: @js((bool) $costoComercial),
        sugerenciaPrecio: @js($esAdministrador ? $sugerenciaPrecio : null),
        precioPublicadoAdmin: @js(
            $esAdministrador
                ? old(
                    'precio_publico',
                    $equipo->precioVigente?->precio_publico
                        ?? ($sugerenciaPrecio['precio_sugerido'] ?? '')
                )
                : null
        ),
        precioMinimoAdmin: @js(
            $esAdministrador
                ? old(
                    'precio_minimo_autorizado',
                    $equipo->precioVigente?->precio_minimo_autorizado
                        ?? ($sugerenciaPrecio['precio_minimo_sugerido'] ?? '')
                )
                : null
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
                    'estado' => $evaluacion['estado'] ?? null,
                    'requiere_autorizacion' => $evaluacion['requiere_autorizacion'] ?? false,
                    'cumple_politica' => $evaluacion['cumple_politica'] ?? null,
                    'descuento' => $evaluacion['descuento'] ?? null,
                    'porcentaje_descuento' => $evaluacion['porcentaje_descuento'] ?? null,
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
        tcAbierto: false,

        init() {
            if (
                this.puedeEvaluar
                && Number(this.precio) > 0
            ) {
                this.$nextTick(() => {
                    this.evaluar();
                });
            }
        },

        usarSugerencia() {
            if (!this.sugerenciaPrecio) {
                return;
            }

            this.precioPublicadoAdmin =
                this.sugerenciaPrecio.precio_sugerido;

            this.precioMinimoAdmin =
                this.sugerenciaPrecio.precio_minimo_sugerido;

            this.precio =
                this.sugerenciaPrecio.precio_sugerido;

            this.$nextTick(() => {
                this.evaluar();
            });
        },

        estadoTexto(estado) {
            const estados = {
                APROBABLE:
                    'Precio permitido',

                REQUIERE_AUTORIZACION:
                    'Requiere autorización',

                REQUIERE_REVISION:
                    'Requiere revisión',

                NO_CUMPLE_POLITICA:
                    'Fuera de política',

                NO_RECOMENDADA:
                    'Este precio genera pérdida',
            };

            return estados[estado]
                ?? 'Evaluación comercial';
        },

        estadoAyuda(estado) {
            const ayudas = {
                APROBABLE:
                    'Puede negociarse sin autorización adicional.',

                REQUIERE_AUTORIZACION:
                    'Está por debajo del mínimo operativo. Si una negociación real llega a este valor, la excepción se resolverá dentro del flujo de Ventas.',

                REQUIERE_REVISION:
                    'Todavía falta definir una referencia comercial o un mínimo operativo.',

                NO_CUMPLE_POLITICA:
                    'La propuesta incumple una regla comercial vigente.',

                NO_RECOMENDADA:
                    'La propuesta queda por debajo del costo comercial actual.',
            };

            return ayudas[estado]
                ?? '';
        },

        estadoClase(estado) {
            if (
                estado === 'APROBABLE'
            ) {
                return 'border-emerald-200 bg-emerald-50 text-emerald-800';
            }

            if (
                estado === 'REQUIERE_AUTORIZACION'
                || estado === 'REQUIERE_REVISION'
            ) {
                return 'border-amber-200 bg-amber-50 text-amber-800';
            }

            return 'border-red-200 bg-red-50 text-red-800';
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

        <div class="flex flex-wrap items-stretch gap-3">
            @if($equipo->precioVigente)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Precio publicado
                    </p>
                    <p class="mt-1 text-lg font-bold text-slate-950">
                        Bs {{ number_format($equipo->precioVigente->precio_publico, 2) }}
                    </p>
                </div>
            @endif

            @if($esAdministrador && $costoComercial)
                <div class="rounded-xl bg-slate-50 px-4 py-3">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                        Costo comercial actual
                    </p>
                    <p class="mt-1 text-lg font-bold text-slate-950">
                        Bs {{ number_format($costoComercial['costo_total'], 2) }}
                    </p>
                </div>
            @endif

            @if(
                $esAdministrador
                && $monedaOrigen
                && $monedaOrigen->codigo !== 'BOB'
            )
                <button
                    type="button"
                    @click="tcAbierto = true"
                    class="group flex min-w-[210px] items-center justify-between gap-4 rounded-xl border px-4 py-3 text-left transition hover:border-blue-300 hover:bg-blue-50/40 {{ $tipoCambioVigente ? 'border-slate-200 bg-white' : 'border-amber-300 bg-amber-50' }}"
                >
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-wide {{ $tipoCambioVigente ? 'text-slate-400' : 'text-amber-700' }}">
                            Dólar global {{ $monedaOrigen->codigo }} → BOB
                        </p>
                        <p class="mt-1 text-lg font-bold text-slate-950">
                            @if($tipoCambioVigente)
                                Bs {{ number_format($tipoCambioVigente->valor, 2) }}
                            @else
                                Sin configurar
                            @endif
                        </p>
                    </div>

                    <span class="text-xs font-semibold text-blue-600 group-hover:text-blue-700">
                        Editar
                    </span>
                </button>
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

    @if($esAdministrador && $equipo->estadoActual?->codigo === 'RECIBIDO')
        <section class="rounded-2xl border border-amber-200 bg-amber-50/60 px-5 py-4 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-slate-950">
                            Equipo aún no publicado para venta
                        </h2>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-amber-800">
                            Recibido
                        </span>
                    </div>
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">
                        Definir un precio no cambia automáticamente la disponibilidad. Cuando Daniel termine de revisar precio y límite de negociación, puede habilitar esta unidad para que aparezca en Nueva venta.
                    </p>
                </div>

                @if($equipo->precioVigente && $costoComercial)
                    <form
                        method="POST"
                        action="{{ route('precios.equipos.habilitar-venta', $equipo) }}"
                        onsubmit="return confirm('¿Habilitar este equipo para venta? Desde ese momento aparecerá en Nueva venta.')"
                        class="shrink-0"
                    >
                        @csrf
                        <button type="submit" class="btn-primary">
                            Habilitar para venta
                        </button>
                    </form>
                @else
                    <div class="rounded-xl border border-amber-200 bg-white px-4 py-3 text-sm text-amber-800">
                        Completa precio y costo comercial antes de habilitar.
                    </div>
                @endif
            </div>
        </section>
    @elseif($esAdministrador && $equipo->estadoActual?->codigo === 'DISPONIBLE')
        <section class="rounded-2xl border border-emerald-200 bg-emerald-50/60 px-5 py-4 shadow-sm">
            <div class="flex flex-col gap-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-semibold text-emerald-950">
                        Disponible para venta
                    </h2>
                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-800">
                        Publicado
                    </span>
                </div>
                <p class="text-sm text-emerald-800">
                    Esta unidad ya puede ser seleccionada desde Nueva venta mientras conserve precio vigente y estado Disponible.
                </p>
            </div>
        </section>
    @endif

    @if($esAdministrador)
        <section class="rounded-2xl border border-blue-200 bg-white shadow-sm">
            <div class="flex flex-col gap-5 border-b border-blue-100 px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-semibold text-slate-950">
                            Sugerencia OneShop
                        </h2>

                        @if($sugerenciaPrecio)
                            <span class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-blue-700">
                                Confianza {{ strtolower($sugerenciaPrecio['confianza']) }}
                            </span>
                        @endif
                    </div>

                    <p class="mt-1 text-sm text-slate-500">
                        Referencia automática para acelerar la decisión de Daniel. El precio final sigue siendo una decisión administrativa.
                    </p>
                </div>

                @if($sugerenciaPrecio)
                    <button
                        type="button"
                        class="btn-primary shrink-0"
                        @click="usarSugerencia()"
                    >
                        Usar sugerencia
                    </button>
                @endif
            </div>

            @if($sugerenciaPrecio)
                <div class="grid gap-4 p-6 lg:grid-cols-[1fr_1fr_1fr_1.35fr]">
                    <div class="rounded-xl bg-blue-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">
                            Precio sugerido
                        </p>
                        <p class="mt-1 text-2xl font-black text-blue-950">
                            Bs {{ number_format($sugerenciaPrecio['precio_sugerido'], 2) }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Rango sugerido de negociación
                        </p>
                        <p class="mt-1 text-xl font-black text-slate-950">
                            Bs {{ number_format($sugerenciaPrecio['precio_minimo_sugerido'], 2) }}
                            <span class="mx-1 text-slate-300">–</span>
                            Bs {{ number_format($sugerenciaPrecio['precio_sugerido'], 2) }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            El extremo inferior es el mínimo sugerido para negociar sin consultar a Daniel.
                        </p>
                    </div>

                    <div class="rounded-xl bg-emerald-50 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">
                            Ganancia estimada por parte
                        </p>
                        <p class="mt-1 text-2xl font-black text-emerald-700">
                            Bs {{ number_format($sugerenciaPrecio['ganancia_parte_sugerida'], 2) }}
                        </p>
                        <p class="mt-1 text-xs leading-5 text-emerald-700/70">
                            Al precio sugerido. En el mínimo: Bs {{ number_format($sugerenciaPrecio['ganancia_parte_minima'], 2) }} por parte.
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Cómo se calculó
                                </p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">
                                    @if($sugerenciaPrecio['fuente'] === 'HISTORIAL_CATEGORIA')
                                        Historial real de OneShop
                                    @else
                                        Referencia inicial de OneShop
                                    @endif
                                </p>
                            </div>

                            <span class="rounded-lg bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                {{ $sugerenciaPrecio['comparables'] }} comparables
                            </span>
                        </div>

                        <ul class="mt-3 space-y-1.5 text-xs leading-5 text-slate-600">
                            @foreach($sugerenciaPrecio['explicacion'] as $explicacion)
                                <li>• {{ $explicacion }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @else
                <div class="p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800">
                        {{ $errorSugerenciaPrecio ?? 'La sugerencia estará disponible cuando exista un costo comercial válido.' }}
                    </div>
                </div>
            @endif
        </section>
    @endif

    {{-- Zona principal: mínima carga visual --}}
    <div class="grid gap-6 xl:grid-cols-[1fr_1.15fr]">

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="font-semibold text-slate-950">
                    Simulador de venta
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    Simula escenarios internos antes de definir o ajustar el precio del equipo.
                </p>
            </div>

            <div class="p-6">
                @if($costoComercial)
                    <label class="text-sm font-medium text-slate-700">
                        ¿A cuánto quieres venderla?
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
                                Costo comercial actual
                            </p>
                            <p class="mt-1 font-bold text-slate-900">
                                Bs {{ number_format($costoComercial['costo_total'], 2) }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Precio publicado
                            </p>
                            <p class="mt-1 font-bold text-slate-900">
                                @if($equipo->precioVigente)
                                    Bs {{ number_format($equipo->precioVigente->precio_publico, 2) }}
                                @else
                                    Sin precio
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
                    @if($costoComercial)
                        <p class="text-sm font-medium text-slate-400">
                            La ganancia aparecerá aquí mientras escribes.
                        </p>
                    @else
                        <div class="max-w-sm">
                            <p class="font-semibold text-slate-700">
                                Simulador pendiente de configuración
                            </p>
                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                Configura el tipo de cambio comercial para habilitar el cálculo de ganancia.
                            </p>
                        </div>
                    @endif
                </div>

                <div
                    x-show="!cargando && !error && resultado"
                    x-cloak
                    class="w-full"
                >
                    <div class="text-center">
                        <p class="text-sm font-bold uppercase tracking-[0.24em] text-slate-500">
                            Ganancia estimada por parte
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
                            Simulación administrativa. No registra una venta ni solicita una aprobación.
                        </p>

                        <div
                            x-show="resultado?.estado"
                            x-cloak
                            class="mx-auto mt-5 max-w-sm rounded-xl border px-4 py-3 text-center"
                            :class="estadoClase(resultado?.estado)"
                        >
                            <p
                                class="text-sm font-bold"
                                x-text="estadoTexto(resultado?.estado)"
                            ></p>
                            <p
                                class="mt-1 text-xs opacity-80"
                                x-text="estadoAyuda(resultado?.estado)"
                            ></p>
                        </div>

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

    {{-- Administración secundaria: colapsada para no saturar --}}
    @if($esAdministrador)
        <details class="card-oneshop">
            <summary class="cursor-pointer select-none px-6 py-5">
                <span class="font-semibold text-slate-950">
                    Administración del precio
                </span>
                <span class="ml-2 text-sm text-slate-500">
                    Precio oficial y rango de negociación
                </span>
            </summary>

            <div class="border-t border-slate-100 p-6">
                <form
                    method="POST"
                    action="{{ route('precios.equipos.store', $equipo) }}"
                    class="grid gap-4 lg:grid-cols-3"
                >
                    @csrf

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Precio publicado
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="precio_publico"
                            x-model="precioPublicadoAdmin"
                            class="input-oneshop mt-1 w-full"
                            required
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Precio mínimo sin autorización
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="precio_minimo_autorizado"
                            x-model="precioMinimoAdmin"
                            class="input-oneshop mt-1 w-full"
                        >
                        <p class="mt-1 text-xs leading-5 text-slate-500">
                            Desde este valor hasta el precio publicado, Ventas podrá negociar sin consultar a Daniel. Las excepciones se resolverán dentro del flujo de Ventas.
                        </p>
                    </div>

                    <div class="flex items-end">
                        <button
                            type="submit"
                            class="btn-primary w-full"
                        >
                            Guardar precio
                        </button>
                    </div>

                    <div class="lg:col-span-3">
                        <label class="text-sm font-medium text-slate-700">
                            Observación
                        </label>
                        <textarea
                            name="observacion"
                            rows="2"
                            class="input-oneshop mt-1 w-full"
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
                                        <th class="px-4 py-3">Sugerido OneShop</th>
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
                                                {{ $precio->precio_sugerido !== null
                                                    ? 'Bs ' . number_format($precio->precio_sugerido, 2)
                                                    : '—' }}
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

    {{-- Configuración global de moneda: fuera del flujo del equipo --}}
    @if(
        $esAdministrador
        && $monedaOrigen
        && $monedaOrigen->codigo !== 'BOB'
    )
        <div
            x-show="tcAbierto"
            x-cloak
            @keydown.escape.window="tcAbierto = false"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 p-4 backdrop-blur-[1px]"
        >
            <div
                @click.outside="tcAbierto = false"
                class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-6 py-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-blue-600">
                            Configuración comercial global
                        </p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            Dólar {{ $monedaOrigen->codigo }} → BOB
                        </h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Se usa automáticamente para recalcular el costo comercial de todos los equipos comprados en {{ $monedaOrigen->codigo }}.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="tcAbierto = false"
                        class="rounded-lg px-2 py-1 text-xl leading-none text-slate-400 hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Cerrar"
                    >
                        ×
                    </button>
                </div>

                <form
                    method="POST"
                    action="{{ route('precios.tipo-cambio.store') }}"
                    class="p-6"
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

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl bg-slate-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Valor vigente
                            </p>
                            <p class="mt-1 text-2xl font-black text-slate-950">
                                {{ $tipoCambioVigente
                                    ? 'Bs ' . number_format($tipoCambioVigente->valor, 2)
                                    : 'Sin configurar' }}
                            </p>
                            @if($tipoCambioVigente)
                                <p class="mt-1 text-xs text-slate-500">
                                    Desde {{ $tipoCambioVigente->fecha_vigencia?->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-slate-700">
                                Nuevo valor comercial
                            </label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm font-semibold text-slate-400">
                                    Bs
                                </span>
                                <input
                                    type="number"
                                    step="0.000001"
                                    min="0.000001"
                                    name="valor_tipo_cambio"
                                    value="{{ $tipoCambioVigente
                                        ? number_format((float) $tipoCambioVigente->valor, 2, '.', '')
                                        : '' }}"
                                    placeholder="12.00"
                                    class="input-oneshop w-full pl-10"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-800">
                        Cambiar este valor afecta las próximas evaluaciones de <strong>todo el inventario en {{ $monedaOrigen->codigo }}</strong>. No modifica el costo histórico registrado de cada equipo.
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="tcAbierto = false"
                            class="btn-secondary"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="btn-primary"
                        >
                            Actualizar para todo el inventario
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>

</x-layouts.oneshop>
