<div
    x-cloak
    x-show="modalIntervencion"
    x-transition.opacity
    class="
        fixed
        inset-0
        z-[100]
        flex
        items-center
        justify-center
        p-4
    "
>

    {{-- Fondo --}}
    <div
        class="
            absolute
            inset-0
            bg-slate-950/70
            backdrop-blur-sm
        "
        @click="!guardando && (modalIntervencion = false)"
    ></div>


    {{-- Ventana --}}
    <div
        x-show="modalIntervencion"
        x-transition
        @click.stop
        class="
            relative
            z-10
            max-h-[94vh]
            w-full
            max-w-3xl
            overflow-y-auto
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-2xl
        "
    >

        {{-- Encabezado --}}
        <div
            class="
                sticky
                top-0
                z-10
                border-b
                border-slate-200
                bg-white
                px-6
                py-5
            "
        >

            <div class="flex items-start justify-between gap-4">

                <div>

                    <h3 class="text-xl font-bold text-slate-900">
                        Registrar intervención
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Registre componentes o servicios realizados durante la preparación.
                    </p>

                </div>


                <button
                    type="button"
                    @click="modalIntervencion = false"
                    :disabled="guardando"
                    class="
                        rounded-lg
                        p-2
                        text-slate-400
                        transition
                        hover:bg-slate-100
                        hover:text-slate-700
                    "
                >
                    <x-ui.icon
                        name="x"
                        size="20"
                    />
                </button>

            </div>


            {{-- Tabs --}}
            <div
                class="
                    mt-5
                    grid
                    grid-cols-1
                    gap-2
                    sm:grid-cols-3
                "
            >

                <button
                    type="button"
                    @click="seleccionarTipo('externo')"
                    :class="
                        tipoIntervencion === 'externo'
                            ? 'border-oneshop-primary bg-oneshop-light text-oneshop-primary'
                            : 'border-slate-200 bg-white text-slate-600'
                    "
                    class="
                        flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        border
                        px-3
                        py-3
                        text-sm
                        font-semibold
                        transition
                    "
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
                            ? 'border-oneshop-primary bg-oneshop-light text-oneshop-primary'
                            : 'border-slate-200 bg-white text-slate-600'
                    "
                    class="
                        flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        border
                        px-3
                        py-3
                        text-sm
                        font-semibold
                        transition
                    "
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
                            ? 'border-oneshop-primary bg-oneshop-light text-oneshop-primary'
                            : 'border-slate-200 bg-white text-slate-600'
                    "
                    class="
                        flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        border
                        px-3
                        py-3
                        text-sm
                        font-semibold
                        transition
                    "
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
            class="p-6"
        >

            {{-- COMPRA EXTERNA --}}
            <div
                x-show="
                    tipoIntervencion ===
                    'externo'
                "
                class="space-y-5"
            >

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Componente
                    </label>

                    <select
                        x-model="externo.producto_id"
                        class="input-oneshop w-full"
                        required
                    >

                        <option value="">
                            Seleccione un componente
                        </option>

                        @foreach($productosComponentes as $producto)

                            <option value="{{ $producto->id }}">
                                {{ $producto->nombre }}

                                @if($producto->modelo)
                                    - {{ $producto->modelo }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
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

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Fecha
                        </label>

                        <input
                            type="date"
                            x-model="externo.fecha"
                            class="input-oneshop w-full"
                        >

                    </div>

                </div>


                <div
                    class="
                        grid
                        grid-cols-1
                        gap-5
                        sm:grid-cols-3
                    "
                >

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Moneda
                        </label>

                        <select
                            x-model="externo.moneda_id"
                            class="input-oneshop w-full"
                        >

                            <option value="">
                                Sin costo
                            </option>

                            @foreach($monedas as $moneda)

                                <option value="{{ $moneda->id }}">
                                    {{ $moneda->codigo }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Monto
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            x-model="externo.monto_origen"
                            class="input-oneshop w-full"
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

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
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


                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Descripción
                    </label>

                    <input
                        type="text"
                        x-model="externo.descripcion"
                        class="input-oneshop w-full"
                        placeholder="Ej.: cargador compatible adquirido para esta unidad"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Referencia
                    </label>

                    <input
                        type="text"
                        x-model="externo.referencia"
                        class="input-oneshop w-full"
                        placeholder="Factura, recibo u otra referencia"
                    >

                </div>

            </div>


            {{-- DESDE STOCK --}}
            <div
                x-show="
                    tipoIntervencion ===
                    'stock'
                "
                class="space-y-5"
            >

                <div
                    class="
                        rounded-xl
                        border
                        border-blue-100
                        bg-oneshop-soft
                        p-4
                        text-sm
                        text-slate-600
                    "
                >
                    El sistema descontará automáticamente la cantidad del almacén donde se encuentra actualmente la unidad.
                </div>


                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Componente
                    </label>

                    <select
                        x-model="stock.producto_id"
                        class="input-oneshop w-full"
                    >

                        <option value="">
                            Seleccione un componente
                        </option>

                        @foreach($productosComponentes as $producto)

                            <option value="{{ $producto->id }}">
                                {{ $producto->nombre }}

                                @if($producto->modelo)
                                    - {{ $producto->modelo }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                </div>


                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
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

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
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

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Descripción
                    </label>

                    <input
                        type="text"
                        x-model="stock.descripcion"
                        class="input-oneshop w-full"
                    >

                </div>

            </div>


            {{-- SERVICIO --}}
            <div
                x-show="
                    tipoIntervencion ===
                    'servicio'
                "
                class="space-y-5"
            >

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Servicio realizado
                    </label>

                    <input
                        type="text"
                        x-model="servicio.descripcion"
                        class="input-oneshop w-full"
                        placeholder="Ej.: instalación de sistema operativo"
                    >

                </div>


                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Inicio
                        </label>

                        <input
                            type="datetime-local"
                            x-model="servicio.fecha_inicio"
                            class="input-oneshop w-full"
                        >

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Finalización
                        </label>

                        <input
                            type="datetime-local"
                            x-model="servicio.fecha_fin"
                            class="input-oneshop w-full"
                        >

                    </div>

                </div>


                <div
                    class="
                        grid
                        grid-cols-1
                        gap-5
                        sm:grid-cols-3
                    "
                >

                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Moneda
                        </label>

                        <select
                            x-model="servicio.moneda_id"
                            class="input-oneshop w-full"
                        >

                            <option value="">
                                Sin costo
                            </option>

                            @foreach($monedas as $moneda)

                                <option value="{{ $moneda->id }}">
                                    {{ $moneda->codigo }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Monto
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            x-model="servicio.monto_origen"
                            class="input-oneshop w-full"
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

                        <label class="mb-2 block text-sm font-semibold text-slate-700">
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


                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Resultado
                    </label>

                    <textarea
                        x-model="servicio.resultado"
                        rows="3"
                        class="input-oneshop w-full"
                        placeholder="Resultado obtenido después del servicio"
                    ></textarea>

                </div>

            </div>


            {{-- Errores --}}
            <div
                x-show="Object.keys(errores).length > 0"
                class="
                    mt-6
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    p-4
                "
            >

                <template
                    x-for="
                        (mensajes, campo)
                        in errores
                    "
                    :key="campo"
                >

                    <p
                        class="text-sm text-red-700"
                        x-text="mensajes[0]"
                    ></p>

                </template>

            </div>


            <div
                x-show="errorGeneral"
                x-text="errorGeneral"
                class="
                    mt-6
                    rounded-xl
                    border
                    border-red-200
                    bg-red-50
                    p-4
                    text-sm
                    font-medium
                    text-red-700
                "
            ></div>


            {{-- Footer --}}
            <div
                class="
                    mt-7
                    flex
                    flex-col-reverse
                    gap-3
                    border-t
                    border-slate-200
                    pt-5
                    sm:flex-row
                    sm:justify-end
                "
            >

                <button
                    type="button"
                    @click="modalIntervencion = false"
                    :disabled="guardando"
                    class="btn-secondary"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    :disabled="guardando"
                    class="
                        inline-flex
                        items-center
                        justify-center
                        gap-2
                        rounded-xl
                        bg-oneshop-primary
                        px-5
                        py-2.5
                        text-sm
                        font-semibold
                        text-white
                        transition
                        hover:bg-oneshop-dark
                        disabled:cursor-not-allowed
                        disabled:opacity-60
                    "
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