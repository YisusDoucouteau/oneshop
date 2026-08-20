<x-layouts.oneshop
    title="Registrar equipo | OneShop"
    page-title="Registrar equipo"
>

    <div class="mx-auto max-w-6xl">

        {{-- Volver --}}
        <div class="mb-6">
            <a
                href="{{ route('inventario.index') }}"
                class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-950"
            >
                ← Volver al inventario
            </a>
        </div>


        {{-- Encabezado --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold tracking-tight text-slate-950">
                Registrar nuevo equipo
            </h1>

            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Registra una unidad física serializada para incorporarla al inventario y comenzar su trazabilidad.
            </p>
        </div>


        {{-- Error de regla de negocio --}}
        @if($errors->has('registro'))
            <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-800">
                    No fue posible registrar el equipo
                </p>

                <p class="mt-1 text-sm text-red-700">
                    {{ $errors->first('registro') }}
                </p>
            </div>
        @endif


        {{-- Errores de validación --}}
        @if($errors->any() && !$errors->has('registro'))
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4">
                <p class="font-semibold text-amber-900">
                    Revisa la información ingresada.
                </p>

                <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-amber-800">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <form
            method="POST"
            action="{{ route('inventario.store') }}"
            class="space-y-6"
        >
            @csrf


            {{-- 1. Producto --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">

                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                            1
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">
                                Producto
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Selecciona el modelo al que pertenece esta unidad.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="p-6">

                    <label
                        for="producto_id"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Producto *
                    </label>

                    <select
                        id="producto_id"
                        name="producto_id"
                        required
                        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                    >
                        <option value="">
                            Selecciona un producto
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

                    @if($productos->isEmpty())
                        <p class="mt-3 text-sm text-amber-600">
                            No existen productos serializados activos. Primero deberá registrarse un producto.
                        </p>
                    @endif

                </div>

            </section>


            {{-- 2. Identificación --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">

                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                            2
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">
                                Identificación
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Identificadores internos y del fabricante.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2">

                    <div>
                        <label
                            for="codigo_interno"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Código interno *
                        </label>

                        <input
                            id="codigo_interno"
                            name="codigo_interno"
                            type="text"
                            required
                            maxlength="50"
                            value="{{ old('codigo_interno') }}"
                            placeholder="Ej. OS-2051"
                            class="w-full rounded-xl border-slate-300 uppercase focus:border-slate-900 focus:ring-slate-900"
                        >

                        <p class="mt-2 text-xs text-slate-500">
                            Identificador único utilizado por OneShop.
                        </p>
                    </div>


                    <div>
                        <label
                            for="serial_fabricante"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Serial del fabricante
                        </label>

                        <input
                            id="serial_fabricante"
                            name="serial_fabricante"
                            type="text"
                            maxlength="150"
                            value="{{ old('serial_fabricante') }}"
                            placeholder="Opcional si no es visible"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >

                        <p class="mt-2 text-xs text-slate-500">
                            Puede quedar vacío si el serial no existe o no es legible.
                        </p>
                    </div>

                </div>

            </section>


            {{-- 3. Ubicación y condición --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">

                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                            3
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">
                                Ubicación y condición
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Situación física inicial del equipo.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2">

                    <div>
                        <label
                            for="almacen_actual_id"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Almacén *
                        </label>

                        <select
                            id="almacen_actual_id"
                            name="almacen_actual_id"
                            required
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="">
                                Selecciona un almacén
                            </option>

                            @foreach($almacenes as $almacen)
                                <option
                                    value="{{ $almacen->id }}"
                                    @selected(old('almacen_actual_id') == $almacen->id)
                                >
                                    {{ $almacen->nombre }}
                                    @if($almacen->principal)
                                        — Principal
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label
                            for="condicion_fisica_id"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Condición física
                        </label>

                        <select
                            id="condicion_fisica_id"
                            name="condicion_fisica_id"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="">
                                Pendiente de clasificación
                            </option>

                            @foreach($condiciones as $condicion)
                                <option
                                    value="{{ $condicion->id }}"
                                    @selected(old('condicion_fisica_id') == $condicion->id)
                                >
                                    {{ $condicion->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

            </section>


            {{-- 4. Procedencia --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">

                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                            4
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">
                                Procedencia
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Relación del equipo con su proceso de ingreso.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="p-6">

                    <input
                        type="hidden"
                        name="detalle_lote_id"
                        value=""
                    >

                    <div class="grid gap-4 md:grid-cols-2">

                        <div class="rounded-xl border-2 border-slate-950 bg-slate-50 p-5">

                            <div class="flex items-start gap-3">

                                <div class="mt-1 h-4 w-4 rounded-full border-4 border-slate-950"></div>

                                <div>
                                    <p class="font-semibold text-slate-900">
                                        Registro sin lote
                                    </p>

                                    <p class="mt-1 text-sm leading-5 text-slate-500">
                                        El equipo será registrado sin una procedencia de importación asociada.
                                    </p>
                                </div>

                            </div>

                        </div>


                        <div class="cursor-not-allowed rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 opacity-60">

                            <div class="flex items-start justify-between gap-3">

                                <div>
                                    <p class="font-semibold text-slate-700">
                                        Desde lote de importación
                                    </p>

                                    <p class="mt-1 text-sm leading-5 text-slate-500">
                                        Permitirá relacionar el equipo con lote, proveedor y costos de origen.
                                    </p>
                                </div>

                                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-500">
                                    Próximamente
                                </span>

                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- 5. Especificaciones --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">

                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                            5
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">
                                Especificaciones técnicas
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Configuración identificada al momento del ingreso.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="grid gap-6 p-6 md:grid-cols-2 xl:grid-cols-3">

                    <div class="xl:col-span-2">
                        <label
                            for="procesador"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Procesador
                        </label>

                        <input
                            id="procesador"
                            name="procesador"
                            type="text"
                            maxlength="150"
                            value="{{ old('procesador') }}"
                            placeholder="Intel Core i5-1145G7"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="generacion_procesador"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Generación
                        </label>

                        <input
                            id="generacion_procesador"
                            name="generacion_procesador"
                            type="text"
                            maxlength="80"
                            value="{{ old('generacion_procesador') }}"
                            placeholder="11"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="ram_gb"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            RAM (GB)
                        </label>

                        <input
                            id="ram_gb"
                            name="ram_gb"
                            type="number"
                            min="0"
                            value="{{ old('ram_gb') }}"
                            placeholder="16"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="almacenamiento_gb"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Almacenamiento (GB)
                        </label>

                        <input
                            id="almacenamiento_gb"
                            name="almacenamiento_gb"
                            type="number"
                            min="0"
                            value="{{ old('almacenamiento_gb') }}"
                            placeholder="512"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="tipo_almacenamiento"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Tipo
                        </label>

                        <select
                            id="tipo_almacenamiento"
                            name="tipo_almacenamiento"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                            <option value="">No especificado</option>
                            <option value="SSD" @selected(old('tipo_almacenamiento') === 'SSD')>SSD</option>
                            <option value="HDD" @selected(old('tipo_almacenamiento') === 'HDD')>HDD</option>
                            <option value="NVMe" @selected(old('tipo_almacenamiento') === 'NVMe')>NVMe</option>
                            <option value="eMMC" @selected(old('tipo_almacenamiento') === 'eMMC')>eMMC</option>
                        </select>
                    </div>


                    <div>
                        <label
                            for="tarjeta_grafica"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Tarjeta gráfica
                        </label>

                        <input
                            id="tarjeta_grafica"
                            name="tarjeta_grafica"
                            type="text"
                            maxlength="150"
                            value="{{ old('tarjeta_grafica') }}"
                            placeholder="Intel Iris Xe"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="pantalla_pulgadas"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Pantalla (pulgadas)
                        </label>

                        <input
                            id="pantalla_pulgadas"
                            name="pantalla_pulgadas"
                            type="number"
                            min="0"
                            max="999.9"
                            step="0.1"
                            value="{{ old('pantalla_pulgadas') }}"
                            placeholder="14.0"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="resolucion"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Resolución
                        </label>

                        <input
                            id="resolucion"
                            name="resolucion"
                            type="text"
                            maxlength="50"
                            value="{{ old('resolucion') }}"
                            placeholder="1920x1080"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="sistema_operativo"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Sistema operativo
                        </label>

                        <input
                            id="sistema_operativo"
                            name="sistema_operativo"
                            type="text"
                            maxlength="100"
                            value="{{ old('sistema_operativo') }}"
                            placeholder="Windows 11 Pro"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>


                    <div>
                        <label
                            for="bateria_porcentaje"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Batería (%)
                        </label>

                        <input
                            id="bateria_porcentaje"
                            name="bateria_porcentaje"
                            type="number"
                            min="0"
                            max="100"
                            value="{{ old('bateria_porcentaje') }}"
                            placeholder="90"
                            class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                        >
                    </div>

                </div>

            </section>


            {{-- 6. Observaciones y confirmación --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <div class="flex items-center gap-4">

                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-950 text-sm font-bold text-white">
                            6
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-950">
                                Confirmación
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Observaciones iniciales antes de incorporar el equipo.
                            </p>
                        </div>

                    </div>
                </div>

                <div class="p-6">

                    <label
                        for="observacion"
                        class="mb-2 block text-sm font-semibold text-slate-700"
                    >
                        Observación
                    </label>

                    <textarea
                        id="observacion"
                        name="observacion"
                        rows="4"
                        placeholder="Detalles relevantes identificados durante el ingreso..."
                        class="w-full rounded-xl border-slate-300 focus:border-slate-900 focus:ring-slate-900"
                    >{{ old('observacion') }}</textarea>


                    <div class="mt-6 rounded-xl bg-slate-50 p-4">

                        <div class="flex items-start gap-3">

                            <div class="mt-0.5 text-lg">
                                ⓘ
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-slate-800">
                                    Estado inicial: Recibido
                                </p>

                                <p class="mt-1 text-sm leading-5 text-slate-500">
                                    Al guardar, el sistema asignará automáticamente el estado RECIBIDO y generará el primer evento de trazabilidad.
                                </p>
                            </div>

                        </div>

                    </div>

                </div>

            </section>


            {{-- Acciones --}}
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('inventario.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    @disabled($productos->isEmpty())
                    class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    Registrar equipo
                </button>

            </div>

        </form>

    </div>

</x-layouts.oneshop>