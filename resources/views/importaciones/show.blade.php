<x-layouts.oneshop
    title="{{ $lote->codigo }} | OneShop"
    page-title="Detalle de importación"
>

    <div class="space-y-6">

        <div>
            <a
                href="{{ route('importaciones.index') }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-950"
            >
                ← Volver a importaciones
            </a>
        </div>


        @if(session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </p>
            </div>
        @endif


        @if($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="font-semibold text-red-800">
                    No fue posible completar la operación.
                </p>

                <ul class="mt-2 list-inside list-disc text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- Cabecera --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">

                <div>
                    <div class="flex flex-wrap items-center gap-3">

                        <h1 class="text-3xl font-bold tracking-tight text-slate-950">
                            {{ $lote->codigo }}
                        </h1>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                            {{ str_replace('_', ' ', $lote->estado) }}
                        </span>

                    </div>

                    <p class="mt-2 text-sm text-slate-500">
                        {{ $lote->referencia_compra ?: 'Sin referencia de compra' }}
                    </p>
                </div>


                <div class="flex flex-col gap-3 sm:flex-row">

                    {{-- Tipo de cambio de referencia --}}
                    <div class="rounded-xl bg-slate-50 px-5 py-4">

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            USD / BOB referencia
                        </p>

                        @if($referenciaUsdBob && ($referenciaUsdBob['tipo_cambio'] ?? null))

                            @php
                                $tcCabecera = $referenciaUsdBob['tipo_cambio'];
                            @endphp

                            <p class="mt-1 text-2xl font-bold text-slate-950">
                                Bs {{ number_format((float) $tcCabecera->valor, 2, ',', '.') }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                BCB vía BCBO

                                @if($referenciaUsdBob['desactualizado'] ?? false)
                                    · último valor local
                                @else
                                    · referencia disponible
                                @endif
                            </p>

                        @else

                            <p class="mt-1 text-sm font-semibold text-amber-700">
                                No disponible
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                Puedes registrar el TC aplicado manualmente.
                            </p>

                        @endif

                    </div>


                    {{-- Recepción --}}
                    <div class="rounded-xl bg-slate-50 px-5 py-4">

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Recepción
                        </p>

                        <p class="mt-1 text-2xl font-bold text-slate-950">
                            {{ $cantidadRecibida }}

                            <span class="text-base font-medium text-slate-400">
                                / {{ $cantidadEsperada }}
                            </span>
                        </p>

                    </div>

                </div>

            </div>


            <div class="mt-8 grid gap-6 sm:grid-cols-2 xl:grid-cols-4">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Proveedor
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $lote->proveedor?->nombre ?? 'No definido' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Origen
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $lote->origen ?: 'No especificado' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Creado
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $lote->created_at?->format('d/m/Y H:i') }}
                    </p>
                </div>


                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Líneas de compra
                    </p>

                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $lote->detalles->count() }}
                    </p>
                </div>

            </div>

        </section>


        {{-- Composición --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h2 class="font-semibold text-slate-950">
                        Composición del lote
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Cada fila representa una línea de compra. Un mismo producto puede aparecer varias veces.
                    </p>
                </div>

            </div>


            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-50">

                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                Producto
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                Esperadas
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                Recibidas
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                Pendientes
                            </th>

                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase text-slate-500">
                                Compra unitaria
                            </th>

                            <th class="px-6 py-4"></th>
                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($lote->detalles as $detalle)

                            @php
                                $pendientes = max(
                                    0,
                                    $detalle->cantidad_esperada - $detalle->cantidad_recibida
                                );
                            @endphp

                            <tr>

                                <td class="px-6 py-5">

                                    <p class="font-semibold text-slate-900">
                                        {{ $detalle->producto->marca?->nombre }}
                                        {{ $detalle->producto->nombre }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ $detalle->producto->modelo ?: $detalle->producto->codigo }}
                                    </p>

                                    @if($detalle->observacion)
                                        <p class="mt-2 max-w-sm text-xs text-slate-500">
                                            {{ $detalle->observacion }}
                                        </p>
                                    @endif

                                </td>


                                <td class="px-6 py-5 text-sm font-semibold text-slate-700">
                                    {{ $detalle->cantidad_esperada }}
                                </td>


                                <td class="px-6 py-5 text-sm font-semibold text-slate-700">
                                    {{ $detalle->cantidad_recibida }}
                                </td>


                                <td class="px-6 py-5">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                        {{ $pendientes }}
                                    </span>
                                </td>


                                <td class="px-6 py-5">

                                    @if($detalle->costo_unitario_origen !== null)

                                        @if($detalle->moneda)

                                            <div>

                                                <p class="font-semibold text-slate-900">
                                                    {{ $detalle->moneda->codigo }}
                                                    {{ number_format(
                                                        (float) $detalle->costo_unitario_origen,
                                                        2,
                                                        ',',
                                                        '.'
                                                    ) }}
                                                </p>


                                                @if(
                                                    $detalle->moneda->codigo === 'USD'
                                                    && $detalle->tipoCambioCompra
                                                )

                                                    <p class="mt-1 text-xs text-slate-500">
                                                        TC aplicado:
                                                        Bs
                                                        {{ number_format(
                                                            (float) $detalle->tipoCambioCompra->valor,
                                                            2,
                                                            ',',
                                                            '.'
                                                        ) }}
                                                        / USD
                                                    </p>

                                                @endif


                                                @if(
                                                    $detalle->costo_unitario_bob !== null
                                                    && $detalle->moneda->codigo !== 'BOB'
                                                )

                                                    <p class="mt-1 text-xs font-medium text-slate-600">
                                                        ≈ Bs
                                                        {{ number_format(
                                                            (float) $detalle->costo_unitario_bob,
                                                            2,
                                                            ',',
                                                            '.'
                                                        ) }}
                                                    </p>

                                                @endif

                                            </div>

                                        @else

                                            <div>

                                                <p class="font-semibold text-amber-700">
                                                    {{ number_format(
                                                        (float) $detalle->costo_unitario_origen,
                                                        2,
                                                        ',',
                                                        '.'
                                                    ) }}
                                                </p>

                                                <p class="mt-1 text-xs text-amber-600">
                                                    Moneda pendiente
                                                </p>

                                            </div>

                                        @endif

                                    @else

                                        <span class="text-sm text-slate-400">
                                            Sin precio registrado
                                        </span>

                                    @endif

                                </td>


                                <td class="px-6 py-5 text-right">

                                    @if(
                                        auth()->user()->tienePermiso('importacion.gestionar')
                                        && $pendientes > 0
                                    )
                                        <span class="text-xs font-semibold text-slate-400">
                                            Recibir equipo →
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-400">
                                            Completo
                                        </span>
                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="6"
                                    class="px-6 py-12 text-center text-sm text-slate-500"
                                >
                                    El lote todavía no tiene productos registrados.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </section>


        {{-- Agregar línea de compra --}}
        @if(
            auth()->user()->tienePermiso('importacion.gestionar')
            && $lote->estado === 'ABIERTO'
        )

            @php
                $monedaAnterior = $monedas->firstWhere(
                    'id',
                    (int) old('moneda_id')
                );

                $codigoMonedaAnterior =
                    $monedaAnterior?->codigo ?? '';

                $tcReferencia =
                    $referenciaUsdBob['tipo_cambio']
                    ?? null;
            @endphp


            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div>
                    <h2 class="font-semibold text-slate-950">
                        Agregar línea de compra
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Define producto, cantidad y datos económicos antes de iniciar su recepción física.
                    </p>
                </div>


                <form
                    method="POST"
                    action="{{ route('importaciones.detalles.store', $lote) }}"
                    class="mt-6"
                    x-data="{
                        moneda: @js($codigoMonedaAnterior),

                        precio: @js(
                            (string) old(
                                'costo_unitario_origen',
                                ''
                            )
                        ),

                        tc: @js(
                            (string) old(
                                'tipo_cambio_aplicado',
                                $tcReferencia?->valor ?? ''
                            )
                        ),

                        formato(valor) {
                            if (
                                valor === ''
                                || valor === null
                                || isNaN(Number(valor))
                            ) {
                                return '—';
                            }

                            return Number(valor).toLocaleString(
                                'es-BO',
                                {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                }
                            );
                        },

                        equivalente() {
                            if (
                                this.precio === ''
                                || this.moneda === ''
                            ) {
                                return '—';
                            }

                            if (this.moneda === 'BOB') {
                                return 'Bs ' + this.formato(
                                    this.precio
                                );
                            }

                            if (
                                this.moneda === 'USD'
                                && this.tc !== ''
                            ) {
                                return 'Bs ' + this.formato(
                                    Number(this.precio)
                                    * Number(this.tc)
                                );
                            }

                            return '—';
                        }
                    }"
                >
                    @csrf


                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                        {{-- Producto --}}
<div
    x-data="{
        modalProducto: false,
        guardandoProducto: false,
        errorProducto: '',

        nuevoProducto: {
            categoria_producto_id: '',
            nueva_categoria_nombre: '',
            marca_id: '',
            nueva_marca_nombre: '',
            nombre: '',
            modelo: '',
            descripcion: '',
            es_serializado: true
        },

        limpiarProducto() {
            this.errorProducto = '';

            this.nuevoProducto = {
                categoria_producto_id: '',
                nueva_categoria_nombre: '',
                marca_id: '',
                nueva_marca_nombre: '',
                nombre: '',
                modelo: '',
                descripcion: '',
                es_serializado: true
            };
        },

        async crearProducto() {
            this.guardandoProducto = true;
            this.errorProducto = '';

            try {
                const respuesta = await fetch(
                    '{{ route('importaciones.catalogo.productos.store') }}',
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
                            this.nuevoProducto
                        )
                    }
                );

                const datos = await respuesta.json();

                if (!respuesta.ok) {
                    if (datos.errors) {
                        const mensajes =
                            Object.values(datos.errors)
                                .flat();

                        this.errorProducto =
                            mensajes.join(' ');
                    } else {
                        this.errorProducto =
                            datos.message
                            ?? 'No fue posible crear el producto.';
                    }

                    return;
                }

                const select =
                    document.getElementById(
                        'producto_id_lote'
                    );

                const opcion =
                    document.createElement(
                        'option'
                    );

                opcion.value =
                    datos.producto.id;

                opcion.textContent =
                    datos.producto.label;

                opcion.selected =
                    true;

                select.appendChild(
                    opcion
                );

                this.modalProducto =
                    false;

                this.limpiarProducto();

            } catch (error) {
                this.errorProducto =
                    'No fue posible comunicarse con el servidor.';
            } finally {
                this.guardandoProducto =
                    false;
            }
        }
    }"
>

    <div class="mb-2 flex items-center justify-between gap-3">

        <label class="block text-sm font-semibold text-slate-700">
            Producto *
        </label>

        <button
            type="button"
            @click="
                limpiarProducto();
                modalProducto = true;
            "
            class="text-xs font-semibold text-slate-700 hover:text-slate-950"
        >
            + Nuevo producto
        </button>

    </div>


    <select
        id="producto_id_lote"
        name="producto_id"
        required
        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
    >

        <option value="">
            Seleccionar producto
        </option>

        @foreach($productos as $producto)

            <option
                value="{{ $producto->id }}"
                @selected(old('producto_id') == $producto->id)
            >
                {{ $producto->marca?->nombre }}
                {{ $producto->nombre }}

                @if($producto->modelo)
                    — {{ $producto->modelo }}
                @endif
            </option>

        @endforeach

    </select>


    {{-- Modal nuevo producto --}}
    <template x-teleport="body">

        <div
            x-show="modalProducto"
            x-cloak
            @keydown.escape.window="modalProducto = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >

            {{-- Fondo --}}
            <div
                class="absolute inset-0 bg-slate-950/50"
                @click="modalProducto = false"
            ></div>


            {{-- Modal --}}
            <div
                x-show="modalProducto"
                x-transition
                class="relative z-10 max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl"
            >

                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-5">

                    <div>
                        <h3 class="text-lg font-bold text-slate-950">
                            Nuevo producto
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Registra modelo.
                        </p>
                    </div>


                    <button
                        type="button"
                        @click="modalProducto = false"
                        class="rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-100 hover:text-slate-900"
                    >
                        ✕
                    </button>

                </div>


                <div class="space-y-5 p-6">

                    {{-- Error --}}
                    <div
                        x-show="errorProducto"
                        class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700"
                        x-text="errorProducto"
                    ></div>


                    <div class="grid gap-5 md:grid-cols-2">

                        {{-- Categoría --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Categoría
                            </label>

                            <select
                                x-model="nuevoProducto.categoria_producto_id"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach($categorias as $categoria)

                                    <option value="{{ $categoria->id }}">
                                        {{ $categoria->nombre }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Nueva categoría --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                O nueva categoría
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProducto.nueva_categoria_nombre"
                                placeholder="Ej. Consola"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Marca --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Marca
                            </label>

                            <select
                                x-model="nuevoProducto.marca_id"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                                <option value="">
                                    Sin marca definida
                                </option>

                                @foreach($marcas as $marca)

                                    <option value="{{ $marca->id }}">
                                        {{ $marca->nombre }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- Nueva marca --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                O nueva marca
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProducto.nueva_marca_nombre"
                                placeholder="Ej. Lenovo"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Nombre --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Nombre *
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProducto.nombre"
                                placeholder="Ej. ThinkPad"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Modelo --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Modelo
                            </label>

                            <input
                                type="text"
                                x-model="nuevoProducto.modelo"
                                placeholder="Ej. T14 Gen 3"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>

                    </div>


                    {{-- Descripción --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Descripción
                        </label>

                        <textarea
                            x-model="nuevoProducto.descripcion"
                            rows="2"
                            placeholder="Información adicional del modelo..."
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        ></textarea>

                    </div>


                    {{-- Serializado --}}
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4">

                        <input
                            type="checkbox"
                            x-model="nuevoProducto.es_serializado"
                            class="rounded border-slate-300 text-slate-950 focus:ring-slate-900"
                        >

                        <div>
                            <p class="text-sm font-semibold text-slate-800">
                                Producto serializado
                            </p>

                            <p class="text-xs text-slate-500">
                                Cada unidad será registrada individualmente en inventario.
                            </p>
                        </div>

                    </label>

                </div>


                <div class="flex justify-end gap-3 border-t border-slate-200 px-6 py-5">

                    <button
                        type="button"
                        @click="modalProducto = false"
                        class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        @click="crearProducto()"
                        :disabled="
                            guardandoProducto
                            || !nuevoProducto.nombre
                            || (
                                !nuevoProducto.categoria_producto_id
                                && !nuevoProducto.nueva_categoria_nombre
                            )
                        "
                        class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span x-show="!guardandoProducto">
                            Crear y seleccionar
                        </span>

                        <span x-show="guardandoProducto">
                            Guardando...
                        </span>
                    </button>

                </div>

            </div>

        </div>

    </template>

</div>


                        {{-- Cantidad --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Cantidad esperada *
                            </label>

                            <input
                                type="number"
                                name="cantidad_esperada"
                                min="1"
                                required
                                value="{{ old('cantidad_esperada', 1) }}"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>


                        {{-- Moneda --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Moneda
                            </label>

                            <select
                                name="moneda_id"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                                @change="
                                    moneda =
                                        $event.target
                                            .selectedOptions[0]
                                            ?.dataset.codigo
                                        || ''
                                "
                            >

                                <option
                                    value=""
                                    data-codigo=""
                                >
                                    No definida
                                </option>

                                @foreach($monedas as $moneda)

                                    <option
                                        value="{{ $moneda->id }}"
                                        data-codigo="{{ $moneda->codigo }}"
                                        @selected(old('moneda_id') == $moneda->id)
                                    >
                                        {{ $moneda->codigo }}
                                        — {{ $moneda->nombre }}
                                    </option>

                                @endforeach

                            </select>

                            <p class="mt-1 text-xs text-slate-500">
                                Si registras precio, la moneda es obligatoria.
                            </p>

                        </div>


                        {{-- Precio --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Precio unitario de compra
                            </label>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                name="costo_unitario_origen"
                                x-model="precio"
                                value="{{ old('costo_unitario_origen') }}"
                                placeholder="Ej. 233.00"
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                Precio pagado por unidad en la moneda seleccionada.
                            </p>

                        </div>


                        {{-- Tipo de cambio aplicado --}}
                        <div
                            x-show="moneda === 'USD'"
                            x-transition
                        >

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Tipo de cambio aplicado
                            </label>


                            @if($tcReferencia)

                                <div class="mb-2 rounded-lg bg-slate-50 px-3 py-2">

                                    <p class="text-xs text-slate-500">
                                        Referencia disponible
                                    </p>

                                    <p class="mt-0.5 text-sm font-semibold text-slate-800">
                                        Bs
                                        {{ number_format(
                                            (float) $tcReferencia->valor,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                        / USD
                                    </p>

                                    <p class="mt-1 text-[11px] text-slate-500">
                                        BCB vía BCBO

                                        @if($referenciaUsdBob['desactualizado'] ?? false)
                                            · último valor local
                                        @endif
                                    </p>

                                </div>

                            @else

                                <div class="mb-2 rounded-lg bg-amber-50 px-3 py-2">
                                    <p class="text-xs font-medium text-amber-700">
                                        No fue posible obtener una referencia automática.
                                    </p>
                                </div>

                            @endif


                            <input
                                type="number"
                                step="0.000001"
                                min="0.000001"
                                name="tipo_cambio_aplicado"
                                x-model="tc"
                                :disabled="moneda !== 'USD'"
                                :required="
                                    moneda === 'USD'
                                    && precio !== ''
                                "
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                Confirma o modifica el TC según el valor realmente utilizado en la compra.
                            </p>

                        </div>


                        {{-- Equivalente --}}
                        <div>

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Equivalente unitario en Bs
                            </label>

                            <div class="flex min-h-[42px] items-center rounded-xl border border-slate-200 bg-slate-50 px-4">

                                <span
                                    class="text-sm font-bold text-slate-900"
                                    x-text="equivalente()"
                                >
                                    —
                                </span>

                            </div>

                            <p class="mt-1 text-xs text-slate-500">
                                Vista previa. El servidor vuelve a calcularlo al guardar.
                            </p>

                        </div>


                        {{-- Observación --}}
                        <div class="md:col-span-2 xl:col-span-3">

                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Observación
                            </label>

                            <input
                                type="text"
                                name="observacion"
                                value="{{ old('observacion') }}"
                                placeholder="Ej. segunda compra del mismo modelo, remate, oferta..."
                                class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                            >

                        </div>

                    </div>


                    <div class="mt-6 flex justify-end">

                        <button
                            type="submit"
                            class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Agregar al lote
                        </button>

                    </div>

                </form>

            </section>

        @endif


        {{-- Eventos logísticos --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h2 class="font-semibold text-slate-950">
                Seguimiento logístico
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Eventos registrados durante el recorrido del lote.
            </p>


            <div class="mt-6 space-y-5">

                @forelse($lote->eventosLogisticos as $evento)

                    <div class="flex gap-4">

                        <div class="mt-1 h-3 w-3 shrink-0 rounded-full bg-slate-900"></div>

                        <div>

                            <p class="text-sm font-semibold text-slate-900">
                                {{ $evento->tipoEvento->nombre }}
                            </p>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $evento->fecha_evento->format('d/m/Y H:i') }}

                                @if($evento->ubicacion)
                                    · {{ $evento->ubicacion }}
                                @endif
                            </p>

                            @if($evento->descripcion)
                                <p class="mt-2 text-sm text-slate-600">
                                    {{ $evento->descripcion }}
                                </p>
                            @endif

                        </div>

                    </div>

                @empty

                    <p class="text-sm text-slate-500">
                        Todavía no existen eventos logísticos registrados.
                    </p>

                @endforelse

            </div>

        </section>

    </div>

</x-layouts.oneshop>