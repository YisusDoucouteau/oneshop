<x-layouts.oneshop
    title="Editar recepción | OneShop"
    page-title="Recepción física"
>
    <div class="mx-auto max-w-5xl">
        {{-- VOLVER --}}
        <div class="mb-5">
            <a
                href="{{ route('importaciones.show', $unidad->detalleLote->lote) }}"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-oneshop-dark"
            >
                <span aria-hidden="true">←</span>
                Volver al lote
            </a>
        </div>

        {{-- CABECERA --}}
        <section class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm">
            <div class="flex flex-col gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                        <x-ui.icon name="monitor-check" size="18" />
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Corrección de recepción
                        </p>

                        <h1 class="mt-0.5 text-xl font-bold text-slate-950">
                            Editar equipo recibido
                        </h1>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ $unidad->codigo_trazabilidad }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if($unidad->grado_recibido)
                        <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-oneshop-dark">
                            Grado {{ $unidad->grado_recibido }}
                        </span>
                    @endif

                    <span class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700">
                        {{ str_replace('_', ' ', $unidad->estado) }}
                    </span>
                </div>
            </div>

            {{-- IDENTIFICACIÓN --}}
            <div class="grid gap-4 border-b border-slate-200 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Marca
                    </p>

                    <p class="mt-1 text-sm font-bold text-slate-800">
                        {{ $unidad->producto?->marca?->nombre ?: 'Sin marca' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Producto
                    </p>

                    <p class="mt-1 text-sm font-bold text-slate-800">
                        {{ $unidad->producto?->nombre ?: 'Sin producto' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Modelo
                    </p>

                    <p class="mt-1 text-sm font-bold text-slate-800">
                        {{ $unidad->producto?->modelo ?: 'Sin modelo' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                        Serial
                    </p>

                    <p class="mt-1 text-sm font-bold text-slate-800">
                        {{ $unidad->serial_fabricante ?: 'No registrado' }}
                    </p>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('importaciones.unidades.actualizar', $unidad) }}"
            >
                @csrf
                @method('PATCH')

                <div class="space-y-6 p-6">
                    {{-- ERRORES --}}
                    @if($errors->any())
                        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                            <p class="text-sm font-bold text-red-800">
                                No se pudieron guardar los cambios.
                            </p>

                            <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- ORIGEN DE COMPRA --}}
                    <section class="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                    Solo lectura
                                </p>

                                <h2 class="mt-1 text-base font-bold text-slate-900">
                                    Origen de compra
                                </h2>

                                <p class="mt-1 text-xs leading-5 text-slate-500">
                                    Estos datos provienen de la compra del lote y no se modifican durante la corrección de recepción.
                                </p>
                            </div>

                            <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-bold text-slate-600">
                                Datos heredados
                            </span>
                        </div>

                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Precio
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    @if($unidad->precio_compra !== null)
                                        {{ number_format((float) $unidad->precio_compra, 2) }}
                                        {{ $unidad->moneda?->codigo }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Equivalente Bs
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    @if($unidad->precio_compra_bob !== null)
                                        Bs {{ number_format((float) $unidad->precio_compra_bob, 2) }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Fecha
                                </p>

                                <p class="mt-1 text-sm font-bold text-slate-800">
                                    {{ $unidad->fecha_compra?->format('d/m/Y') ?? '—' }}
                                </p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                                    Referencia
                                </p>

                                <p class="mt-1 break-words text-sm font-bold text-slate-800">
                                    {{ $unidad->referencia_compra ?: '—' }}
                                </p>
                            </div>
                        </div>
                    </section>

                    {{-- DATOS FÍSICOS --}}
                    <section class="rounded-2xl border border-blue-100 bg-white">
                        <div class="border-b border-blue-100 bg-blue-50/50 px-5 py-4">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                Datos observados
                            </p>

                            <h2 class="mt-1 text-base font-bold text-slate-950">
                                Información física del equipo
                            </h2>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Corrige únicamente lo verificado durante la recepción física.
                            </p>
                        </div>

                        <div class="grid gap-5 p-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Procesador
                                </label>

                                <input
                                    name="procesador"
                                    value="{{ old('procesador', $unidad->procesador) }}"
                                    placeholder="Ej. Intel Core i5-10310U"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Generación
                                </label>

                                <input
                                    name="generacion_procesador"
                                    value="{{ old('generacion_procesador', $unidad->generacion_procesador) }}"
                                    placeholder="Ej. 10th"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    RAM
                                </label>

                                <div class="relative">
                                    <input
                                        type="number"
                                        min="0"
                                        name="ram_gb"
                                        value="{{ old('ram_gb', $unidad->ram_gb) }}"
                                        placeholder="0"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">
                                        GB
                                    </span>
                                </div>

                                <p class="mt-1.5 text-xs text-slate-500">
                                    Usa 0 si el equipo llegó sin memoria RAM.
                                </p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Almacenamiento
                                </label>

                                <div class="relative">
                                    <input
                                        type="number"
                                        min="0"
                                        name="almacenamiento_gb"
                                        value="{{ old('almacenamiento_gb', $unidad->almacenamiento_gb) }}"
                                        placeholder="0"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">
                                        GB
                                    </span>
                                </div>

                                <p class="mt-1.5 text-xs text-slate-500">
                                    Usa 0 si el equipo llegó sin unidad de almacenamiento.
                                </p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Tipo de almacenamiento
                                </label>

                                <select
                                    name="tipo_almacenamiento"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                                    <option value="">
                                        Seleccionar
                                    </option>

                                    <option
                                        value="SSD"
                                        @selected(old('tipo_almacenamiento', $unidad->tipo_almacenamiento) === 'SSD')
                                    >
                                        SSD
                                    </option>

                                    <option
                                        value="NVME"
                                        @selected(old('tipo_almacenamiento', $unidad->tipo_almacenamiento) === 'NVME')
                                    >
                                        NVMe
                                    </option>

                                    <option
                                        value="HDD"
                                        @selected(old('tipo_almacenamiento', $unidad->tipo_almacenamiento) === 'HDD')
                                    >
                                        HDD
                                    </option>

                                    <option
                                        value="EMMC"
                                        @selected(old('tipo_almacenamiento', $unidad->tipo_almacenamiento) === 'EMMC')
                                    >
                                        eMMC
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Tarjeta gráfica
                                </label>

                                <input
                                    name="tarjeta_grafica"
                                    value="{{ old('tarjeta_grafica', $unidad->tarjeta_grafica) }}"
                                    placeholder="Ej. Intel UHD Graphics"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Serial fabricante
                                </label>

                                <input
                                    name="serial_fabricante"
                                    value="{{ old('serial_fabricante', $unidad->serial_fabricante) }}"
                                    placeholder="Ej. ABC123456"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Cargador
                                </label>

                                <select
                                    name="tiene_cargador"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                                    <option
                                        value="1"
                                        @selected(
                                            (string) old(
                                                'tiene_cargador',
                                                $unidad->tiene_cargador ? '1' : '0'
                                            ) === '1'
                                        )
                                    >
                                        Sí, llegó con cargador
                                    </option>

                                    <option
                                        value="0"
                                        @selected(
                                            (string) old(
                                                'tiene_cargador',
                                                $unidad->tiene_cargador ? '1' : '0'
                                            ) === '0'
                                        )
                                    >
                                        No llegó con cargador
                                    </option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Grado recibido
                                    <span class="text-red-700">*</span>
                                </label>

                                <div class="grid gap-3 sm:grid-cols-3">
                                    @foreach([
                                        'A' => '90–100%',
                                        'B' => '70–90%',
                                        'C' => '50–70%',
                                    ] as $grado => $rango)
                                        <label class="cursor-pointer">
                                            <input
                                                type="radio"
                                                name="grado_recibido"
                                                value="{{ $grado }}"
                                                class="peer sr-only"
                                                required
                                                @checked(
                                                    old(
                                                        'grado_recibido',
                                                        $unidad->grado_recibido
                                                    ) === $grado
                                                )
                                            >

                                            <span class="block rounded-xl border border-slate-200 bg-white p-4 transition peer-checked:border-blue-300 peer-checked:bg-blue-50 peer-checked:ring-2 peer-checked:ring-blue-100">
                                                <span class="block text-sm font-black text-slate-900">
                                                    Grado {{ $grado }}
                                                </span>

                                                <span class="mt-1 block text-xs text-slate-500">
                                                    {{ $rango }}
                                                </span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </section>

                    {{-- DETALLES ADICIONALES --}}
                    <section class="rounded-2xl border border-slate-200 bg-white">
                        <div class="border-b border-slate-200 px-5 py-4">
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                                Complementario
                            </p>

                            <h2 class="mt-1 text-base font-bold text-slate-950">
                                Detalles adicionales
                            </h2>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                Información técnica o de servicio observada al recibir la unidad.
                            </p>
                        </div>

                        <div class="grid gap-5 p-5 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Sistema operativo
                                </label>

                                <input
                                    name="sistema_operativo"
                                    value="{{ old('sistema_operativo', $unidad->sistema_operativo) }}"
                                    placeholder="Ej. Windows 11 Pro"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Resolución
                                </label>

                                <input
                                    name="resolucion"
                                    value="{{ old('resolucion', $unidad->resolucion) }}"
                                    placeholder="Ej. 1920x1080"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Pantalla
                                </label>

                                <div class="relative">
                                    <input
                                        type="number"
                                        min="0"
                                        step="0.1"
                                        name="pantalla_pulgadas"
                                        value="{{ old('pantalla_pulgadas', $unidad->pantalla_pulgadas) }}"
                                        placeholder="0.0"
                                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 pr-12 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                    >

                                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">
                                        pulg.
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Servicio requerido
                                </label>

                                <input
                                    name="servicio_requerido"
                                    value="{{ old('servicio_requerido', $unidad->servicio_requerido) }}"
                                    placeholder="Ej. instalar SSD, RAM o cargador"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >

                                <p class="mt-1.5 text-xs text-slate-500">
                                    Déjalo vacío si la unidad no requiere intervención.
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Observación de recepción
                                </label>

                                <textarea
                                    name="observacion_revision"
                                    rows="4"
                                    placeholder="Describe daños, faltantes, estado físico u otras observaciones."
                                    class="w-full resize-none rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-oneshop-primary focus:outline-none focus:ring-4 focus:ring-blue-100"
                                >{{ old('observacion_revision', $unidad->observacion_revision) }}</textarea>
                            </div>
                        </div>
                    </section>
                </div>

                {{-- FOOTER --}}
                <div class="flex flex-col-reverse gap-3 border-t border-blue-100 bg-blue-50/40 px-6 py-4 sm:flex-row sm:justify-end">
                    <a
                        href="{{ route('importaciones.show', $unidad->detalleLote->lote) }}"
                        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-center text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Cancelar
                    </a>

                    <button
                        type="submit"
                        class="rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100"
                    >
                        Guardar cambios
                    </button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.oneshop>
