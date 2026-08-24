<x-layouts.oneshop
    title="Recibir equipo | {{ $lote->codigo }} | OneShop"
    page-title="Recepción de equipo"
>

    <div class="mx-auto max-w-6xl space-y-6">

        {{-- Volver --}}
        <div>
            <a
                href="{{ route('importaciones.show', $lote) }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-950"
            >
                ← Volver a {{ $lote->codigo }}
            </a>
        </div>


        {{-- Encabezado --}}
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-950">
                Recibir equipo
            </h1>

            <p class="mt-2 text-sm text-slate-500">
                Registra una unidad física perteneciente a esta línea de compra.
            </p>
        </div>


        {{-- Errores --}}
        @if($errors->any())

            <div class="rounded-2xl border border-red-200 bg-red-50 p-4">

                <p class="font-semibold text-red-800">
                    No fue posible registrar la recepción.
                </p>

                <ul class="mt-2 list-inside list-disc text-sm text-red-700">

                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- Resumen de la línea --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Producto
                    </p>

                    <h2 class="mt-2 text-xl font-bold text-slate-950">
                        {{ $detalle->producto->marca?->nombre }}
                        {{ $detalle->producto->nombre }}
                    </h2>

                    @if($detalle->producto->modelo)

                        <p class="mt-1 text-sm text-slate-500">
                            Modelo:
                            {{ $detalle->producto->modelo }}
                        </p>

                    @endif

                    <p class="mt-1 text-sm text-slate-500">
                        Código de catálogo:
                        {{ $detalle->producto->codigo }}
                    </p>

                </div>


                <div class="grid grid-cols-3 gap-3">

                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-center">

                        <p class="text-xs font-semibold uppercase text-slate-400">
                            Esperadas
                        </p>

                        <p class="mt-1 text-xl font-bold text-slate-950">
                            {{ $detalle->cantidad_esperada }}
                        </p>

                    </div>


                    <div class="rounded-xl bg-slate-50 px-4 py-3 text-center">

                        <p class="text-xs font-semibold uppercase text-slate-400">
                            Recibidas
                        </p>

                        <p class="mt-1 text-xl font-bold text-slate-950">
                            {{ $detalle->cantidad_recibida }}
                        </p>

                    </div>


                    <div class="rounded-xl bg-slate-950 px-4 py-3 text-center">

                        <p class="text-xs font-semibold uppercase text-slate-400">
                            Pendientes
                        </p>

                        <p class="mt-1 text-xl font-bold text-white">
                            {{ $pendientes }}
                        </p>

                    </div>

                </div>

            </div>


            {{-- Información económica --}}
            @if($detalle->costo_unitario_origen !== null)

                <div class="mt-6 border-t border-slate-100 pt-5">

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Compra unitaria registrada
                    </p>


                    @if($detalle->moneda)

                        <p class="mt-2 text-sm font-bold text-slate-900">
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

                            <p class="mt-1 text-xs text-slate-500">
                                Equivalente:
                                Bs
                                {{ number_format(
                                    (float) $detalle->costo_unitario_bob,
                                    2,
                                    ',',
                                    '.'
                                ) }}
                            </p>

                        @endif

                    @else

                        <p class="mt-2 text-sm font-semibold text-amber-700">
                            Precio histórico con moneda pendiente de identificación.
                        </p>

                    @endif

                </div>

            @endif

        </section>


        <form
            method="POST"
            action="{{ route(
                'importaciones.recepcion.store',
                [
                    'lote' => $lote,
                    'detalle' => $detalle,
                ]
            ) }}"
            class="space-y-6"
        >

            @csrf


            {{-- Identificación física --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div>
                    <h2 class="text-lg font-semibold text-slate-950">
                        Identificación física
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Datos que individualizan esta unidad dentro del inventario.
                    </p>
                </div>


                <div class="mt-6 grid gap-5 md:grid-cols-2">

                    {{-- Código interno --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Código interno *
                        </label>

                        <input
                            type="text"
                            name="codigo_interno"
                            value="{{ old('codigo_interno') }}"
                            maxlength="50"
                            required
                            autocomplete="off"
                            placeholder="Ej. EQ-2026-001"
                            class="w-full rounded-xl border-slate-300 uppercase focus:border-slate-900 focus:ring-slate-900"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Identificador único asignado por OneShop.
                        </p>

                    </div>


                    {{-- Serial --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Serial del fabricante
                        </label>

                        <input
                            type="text"
                            name="serial_fabricante"
                            value="{{ old('serial_fabricante') }}"
                            maxlength="150"
                            autocomplete="off"
                            placeholder="Ej. PF3ABC123"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Puede quedar vacío si todavía no es visible o verificable.
                        </p>

                    </div>


                    {{-- Almacén --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Almacén de recepción *
                        </label>

                        <select
                            name="almacen_actual_id"
                            required
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                            <option value="">
                                Seleccionar almacén
                            </option>

                            @foreach($almacenes as $almacen)

                                <option
                                    value="{{ $almacen->id }}"
                                    @selected(
                                        old('almacen_actual_id')
                                        == $almacen->id
                                    )
                                >
                                    {{ $almacen->nombre }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- Condición física --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Condición física
                        </label>

                        <select
                            name="condicion_fisica_id"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                            <option value="">
                                Sin definir
                            </option>

                            @foreach($condiciones as $condicion)

                                <option
                                    value="{{ $condicion->id }}"
                                    @selected(
                                        old('condicion_fisica_id')
                                        == $condicion->id
                                    )
                                >
                                    {{ $condicion->nombre }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

            </section>


            {{-- Especificaciones --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div>
                    <h2 class="text-lg font-semibold text-slate-950">
                        Especificaciones técnicas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Información técnica identificada durante la recepción.
                        Los datos que todavía no se conozcan pueden quedar vacíos.
                    </p>
                </div>


                <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">

                    {{-- Procesador --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Procesador
                        </label>

                        <input
                            type="text"
                            name="procesador"
                            value="{{ old('procesador') }}"
                            maxlength="150"
                            placeholder="Ej. Intel Core i5-1145G7"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Generación --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Generación del procesador
                        </label>

                        <input
                            type="text"
                            name="generacion_procesador"
                            value="{{ old('generacion_procesador') }}"
                            maxlength="80"
                            placeholder="Ej. 11ª generación"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- RAM --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            RAM (GB)
                        </label>

                        <input
                            type="number"
                            name="ram_gb"
                            value="{{ old('ram_gb') }}"
                            min="0"
                            max="65535"
                            placeholder="16"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Almacenamiento --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Almacenamiento (GB)
                        </label>

                        <input
                            type="number"
                            name="almacenamiento_gb"
                            value="{{ old('almacenamiento_gb') }}"
                            min="0"
                            placeholder="512"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Tipo almacenamiento --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tipo de almacenamiento
                        </label>

                        <select
                            name="tipo_almacenamiento"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                            <option value="">
                                Sin definir
                            </option>

                            <option
                                value="SSD"
                                @selected(old('tipo_almacenamiento') === 'SSD')
                            >
                                SSD
                            </option>

                            <option
                                value="NVME"
                                @selected(old('tipo_almacenamiento') === 'NVME')
                            >
                                NVMe
                            </option>

                            <option
                                value="HDD"
                                @selected(old('tipo_almacenamiento') === 'HDD')
                            >
                                HDD
                            </option>

                            <option
                                value="EMMC"
                                @selected(old('tipo_almacenamiento') === 'EMMC')
                            >
                                eMMC
                            </option>

                            <option
                                value="OTRO"
                                @selected(old('tipo_almacenamiento') === 'OTRO')
                            >
                                Otro
                            </option>

                        </select>

                    </div>


                    {{-- Gráfica --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Tarjeta gráfica
                        </label>

                        <input
                            type="text"
                            name="tarjeta_grafica"
                            value="{{ old('tarjeta_grafica') }}"
                            maxlength="150"
                            placeholder="Ej. Intel Iris Xe"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Pantalla --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Pantalla (pulgadas)
                        </label>

                        <input
                            type="number"
                            name="pantalla_pulgadas"
                            value="{{ old('pantalla_pulgadas') }}"
                            step="0.1"
                            min="0"
                            max="999.9"
                            placeholder="14.0"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Resolución --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Resolución
                        </label>

                        <input
                            type="text"
                            name="resolucion"
                            value="{{ old('resolucion') }}"
                            maxlength="50"
                            placeholder="Ej. 1920x1080"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Sistema operativo --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Sistema operativo
                        </label>

                        <input
                            type="text"
                            name="sistema_operativo"
                            value="{{ old('sistema_operativo') }}"
                            maxlength="100"
                            placeholder="Ej. Windows 11 Pro"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>


                    {{-- Batería --}}
                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Salud de batería (%)
                        </label>

                        <input
                            type="number"
                            name="bateria_porcentaje"
                            value="{{ old('bateria_porcentaje') }}"
                            min="0"
                            max="100"
                            placeholder="90"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                    </div>

                </div>

            </section>


            {{-- Observación --}}
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <h2 class="text-lg font-semibold text-slate-950">
                    Observación de recepción
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Registra detalles relevantes encontrados al recibir esta unidad.
                </p>


                <textarea
                    name="observacion"
                    rows="4"
                    placeholder="Ej. Equipo recibido con cargador, pequeño desgaste en tapa..."
                    class="mt-5 w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                >{{ old('observacion') }}</textarea>

            </section>


            {{-- Acciones --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('importaciones.show', $lote) }}"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Cancelar
                </a>


                <button
                    type="submit"
                    class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Confirmar recepción
                </button>

            </div>

        </form>

    </div>

</x-layouts.oneshop>