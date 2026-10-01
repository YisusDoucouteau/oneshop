<div
    x-cloak
    x-show="modalIntervencion"
    x-transition.opacity
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tituloModalIntervencion"
>
    <div
        class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
        @click="
            if (
                !guardando
            ) {
                modalIntervencion = false;
            }
        "
    ></div>

    <div
        x-show="modalIntervencion"
        x-transition
        @click.stop
        class="relative z-10 flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl"
    >
        {{-- CABECERA --}}
        <div class="shrink-0 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                        <x-ui.icon
                            name="wrench"
                            size="20"
                        />
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Preparación técnica
                        </p>

                        <h3
                            id="tituloModalIntervencion"
                            class="mt-0.5 text-xl font-bold text-slate-950"
                        >
                            Registrar intervención
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Registra componentes o servicios realizados sobre esta unidad.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="modalIntervencion = false"
                    :disabled="guardando"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-100 hover:text-slate-900 disabled:opacity-50"
                    aria-label="Cerrar"
                >
                    <x-ui.icon
                        name="x"
                        size="18"
                    />
                </button>
            </div>

            {{-- TIPO --}}
            <div class="mt-5 grid gap-2 sm:grid-cols-3">
                <button
                    type="button"
                    @click="seleccionarTipo('externo')"
                    :class="
                        tipoIntervencion === 'externo'
                            ? 'border-blue-300 bg-blue-50 ring-2 ring-blue-100'
                            : 'border-slate-200 bg-white hover:bg-slate-50'
                    "
                    class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-bold text-slate-700 transition"
                >
                    <x-ui.icon
                        name="cart"
                        size="17"
                    />

                    Compra externa
                </button>

                <button
                    type="button"
                    @click="seleccionarTipo('stock')"
                    :class="
                        tipoIntervencion === 'stock'
                            ? 'border-blue-300 bg-blue-50 ring-2 ring-blue-100'
                            : 'border-slate-200 bg-white hover:bg-slate-50'
                    "
                    class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-bold text-slate-700 transition"
                >
                    <x-ui.icon
                        name="warehouse"
                        size="17"
                    />

                    Desde stock
                </button>

                <button
                    type="button"
                    @click="seleccionarTipo('servicio')"
                    :class="
                        tipoIntervencion === 'servicio'
                            ? 'border-blue-300 bg-blue-50 ring-2 ring-blue-100'
                            : 'border-slate-200 bg-white hover:bg-slate-50'
                    "
                    class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-sm font-bold text-slate-700 transition"
                >
                    <x-ui.icon
                        name="wrench"
                        size="17"
                    />

                    Servicio técnico
                </button>
            </div>
        </div>

        <form
            @submit.prevent="guardar"
            class="flex min-h-0 flex-1 flex-col"
            novalidate
        >
            <div class="min-h-0 flex-1 overflow-y-auto p-6">
                {{-- COMPRA EXTERNA --}}
                <section
                    x-show="
                        tipoIntervencion
                        === 'externo'
                    "
                    x-transition
                    class="space-y-5"
                >
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Componente adquirido
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Utiliza esta opción cuando el repuesto se compró específicamente para la unidad.
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Componente
                            <span class="text-red-700">*</span>
                        </label>

                        <select
                            x-model="externo.producto_id"
                            class="input-oneshop w-full"
                        >
                            <option value="">
                                Seleccione un componente
                            </option>

                            @foreach(
                                $productosComponentes
                                as $producto
                            )
                                <option value="{{ $producto->id }}">
                                    {{
                                        $producto
                                            ->nombre
                                    }}

                                    @if(
                                        $producto
                                            ->modelo
                                    )
                                        -
                                        {{
                                            $producto
                                                ->modelo
                                        }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Cantidad
                            </label>

                            <input
                                type="number"
                                min="1"
                                x-model.number="externo.cantidad"
                                class="input-oneshop w-full"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Fecha
                            </label>

                            <input
                                type="date"
                                x-model="externo.fecha"
                                class="input-oneshop w-full"
                            >
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <p class="mb-4 text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Costo del componente
                        </p>

                        <div class="grid gap-5 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Moneda
                                </label>

                                <select
                                    x-model="externo.moneda_id"
                                    class="input-oneshop w-full"
                                >
                                    <option value="">
                                        Sin costo
                                    </option>

                                    @foreach(
                                        $monedas
                                        as $moneda
                                    )
                                        <option value="{{ $moneda->id }}">
                                            {{
                                                $moneda
                                                    ->codigo
                                            }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Monto
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    x-model="externo.monto_origen"
                                    class="input-oneshop w-full"
                                    placeholder="0.00"
                                >
                            </div>

                            <div
                                x-show="
                                    codigoMoneda(
                                        externo.moneda_id
                                    ) === 'USD'
                                    ||
                                    codigoMoneda(
                                        externo.moneda_id
                                    ) === 'USDT'
                                "
                            >
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Tipo de cambio
                                </label>

                                <input
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    x-model="externo.tipo_cambio_aplicado"
                                    class="input-oneshop w-full"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Descripción
                        </label>

                        <input
                            type="text"
                            x-model="externo.descripcion"
                            class="input-oneshop w-full"
                            placeholder="Ej. cargador compatible adquirido para esta unidad"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Referencia
                        </label>

                        <input
                            type="text"
                            x-model="externo.referencia"
                            class="input-oneshop w-full"
                            placeholder="Factura, recibo u otra referencia"
                        >
                    </div>
                </section>

                {{-- DESDE STOCK --}}
                <section
                    x-show="
                        tipoIntervencion
                        === 'stock'
                    "
                    x-transition
                    class="space-y-5"
                >
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Componente interno
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Asigna un componente disponible en existencias de OneShop.
                        </p>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50/50 px-4 py-3 text-sm leading-6 text-slate-700">
                        El sistema descontará automáticamente la cantidad del almacén donde se encuentra actualmente la unidad.
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Componente
                        </label>

                        <select
                            x-model="stock.producto_id"
                            class="input-oneshop w-full"
                        >
                            <option value="">
                                Seleccione un componente
                            </option>

                            @foreach(
                                $productosComponentes
                                as $producto
                            )
                                <option value="{{ $producto->id }}">
                                    {{
                                        $producto
                                            ->nombre
                                    }}

                                    @if(
                                        $producto
                                            ->modelo
                                    )
                                        -
                                        {{
                                            $producto
                                                ->modelo
                                        }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Cantidad
                            </label>

                            <input
                                type="number"
                                min="1"
                                x-model.number="stock.cantidad"
                                class="input-oneshop w-full"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Fecha
                            </label>

                            <input
                                type="date"
                                x-model="stock.fecha"
                                class="input-oneshop w-full"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Descripción
                        </label>

                        <input
                            type="text"
                            x-model="stock.descripcion"
                            class="input-oneshop w-full"
                            placeholder="Ej. SSD asignado desde stock"
                        >
                    </div>
                </section>

                {{-- SERVICIO --}}
                <section
                    x-show="
                        tipoIntervencion
                        === 'servicio'
                    "
                    x-transition
                    class="space-y-5"
                >
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Trabajo técnico
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Registra una reparación, instalación, configuración u otro trabajo realizado.
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Servicio realizado
                        </label>

                        <input
                            type="text"
                            x-model="servicio.descripcion"
                            class="input-oneshop w-full"
                            placeholder="Ej. instalación de sistema operativo"
                        >
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Inicio
                            </label>

                            <input
                                type="datetime-local"
                                x-model="servicio.fecha_inicio"
                                class="input-oneshop w-full"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Finalización
                            </label>

                            <input
                                type="datetime-local"
                                x-model="servicio.fecha_fin"
                                class="input-oneshop w-full"
                            >
                        </div>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                        <p class="mb-4 text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Costo del servicio
                        </p>

                        <div class="grid gap-5 sm:grid-cols-3">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Moneda
                                </label>

                                <select
                                    x-model="servicio.moneda_id"
                                    class="input-oneshop w-full"
                                >
                                    <option value="">
                                        Sin costo
                                    </option>

                                    @foreach(
                                        $monedas
                                        as $moneda
                                    )
                                        <option value="{{ $moneda->id }}">
                                            {{
                                                $moneda
                                                    ->codigo
                                            }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Monto
                                </label>

                                <input
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    x-model="servicio.monto_origen"
                                    class="input-oneshop w-full"
                                    placeholder="0.00"
                                >
                            </div>

                            <div
                                x-show="
                                    codigoMoneda(
                                        servicio.moneda_id
                                    ) === 'USD'
                                    ||
                                    codigoMoneda(
                                        servicio.moneda_id
                                    ) === 'USDT'
                                "
                            >
                                <label class="mb-2 block text-sm font-bold text-slate-700">
                                    Tipo de cambio
                                </label>

                                <input
                                    type="number"
                                    min="0.000001"
                                    step="0.000001"
                                    x-model="servicio.tipo_cambio_aplicado"
                                    class="input-oneshop w-full"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Resultado
                        </label>

                        <textarea
                            x-model="servicio.resultado"
                            rows="3"
                            class="input-oneshop w-full"
                            placeholder="Resultado obtenido después del servicio"
                        ></textarea>
                    </div>
                </section>

                {{-- ERRORES --}}
                <div
                    x-show="
                        Object
                            .keys(
                                errores
                            )
                            .length
                        > 0
                    "
                    class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4"
                >
                    <template
                        x-for="
                            (
                                mensajes,
                                campo
                            )
                            in errores
                        "
                        :key="campo"
                    >
                        <p
                            class="text-sm text-red-800"
                            x-text="mensajes[0]"
                        ></p>
                    </template>
                </div>

                <div
                    x-show="errorGeneral"
                    x-text="errorGeneral"
                    class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800"
                ></div>
            </div>

            {{-- FOOTER --}}
            <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-blue-100 bg-blue-50/40 px-6 py-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    @click="modalIntervencion = false"
                    :disabled="guardando"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    :disabled="guardando"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-ui.icon
                        name="check"
                        size="17"
                    />

                    <span
                        x-text="
                            guardando
                                ? 'Guardando...'
                                : 'Registrar intervención'
                        "
                    ></span>
                </button>
            </div>
        </form>
    </div>
</div>
