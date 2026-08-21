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
                            value="{{ old('codigo') }}"
                            placeholder="IMP-2026-001"
                            class="w-full rounded-xl border-slate-300 uppercase focus:border-slate-900 focus:ring-slate-900"
                        >
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


                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Proveedor
                        </label>

                        <select
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