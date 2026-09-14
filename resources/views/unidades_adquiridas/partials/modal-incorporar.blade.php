<div
    x-cloak
    x-show="modalIncorporacion"
    x-transition.opacity
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
>

    {{-- Fondo --}}
    <div
        class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
        @click="!guardando && (modalIncorporacion = false)"
    ></div>


    {{-- Modal --}}
    <div
        x-show="modalIncorporacion"
        x-transition
        @click.stop
        class="
            relative
            z-10
            w-full
            max-w-xl
            overflow-hidden
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-2xl
        "
    >

        {{-- Header --}}
        <div
            class="
                flex
                items-start
                justify-between
                border-b
                border-slate-200
                px-6
                py-5
            "
        >

            <div class="flex gap-3">

                <div
                    class="
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-xl
                        bg-oneshop-light
                        text-oneshop-primary
                    "
                >
                    <x-ui.icon
                        name="package"
                        size="20"
                    />
                </div>


                <div>

                    <h3
                        class="
                            text-lg
                            font-bold
                            text-slate-900
                        "
                    >
                        Incorporar al inventario
                    </h3>

                    <p
                        class="
                            mt-1
                            text-sm
                            text-slate-500
                        "
                    >
                        Daniel confirmará la incorporación formal de esta unidad en Oruro.
                    </p>

                </div>

            </div>


            <button
                type="button"
                @click="modalIncorporacion = false"
                :disabled="guardando"
                class="
                    rounded-lg
                    p-2
                    text-slate-400
                    transition
                    hover:bg-slate-100
                    hover:text-slate-700
                    disabled:opacity-50
                "
            >
                <x-ui.icon
                    name="x"
                    size="20"
                />
            </button>

        </div>


        {{-- Unidad --}}
        <div class="border-b border-slate-100 bg-slate-50 px-6 py-4">

            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Unidad
            </p>

            <p class="mt-1 font-semibold text-slate-900">
                {{
                    $unidad->codigo_trazabilidad
                    ??
                    'Sin código de trazabilidad'
                }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
                {{
                    $unidad->producto?->nombre
                    ??
                    $unidad->nombre_equipo
                    ??
                    'Equipo'
                }}

                @if($unidad->producto?->modelo ?? $unidad->modelo_equipo)

                    · {{
                        $unidad->producto?->modelo
                        ??
                        $unidad->modelo_equipo
                    }}

                @endif
            </p>

        </div>


        <form
            @submit.prevent="incorporarUnidad"
            class="p-6"
        >

            <div class="space-y-5">


                {{-- Condición --}}
                <div>

                    <label
                        for="condicion_fisica_id"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-slate-700
                        "
                    >
                        Condición física
                        <span class="text-red-500">*</span>
                    </label>


                    <select
                        id="condicion_fisica_id"
                        x-model="formulario.condicion_fisica_id"
                        class="input-oneshop w-full"
                        required
                    >

                        <option value="">
                            Seleccione una condición
                        </option>

                        @foreach($condicionesFisicas as $condicion)

                            <option value="{{ $condicion->id }}">
                                {{ $condicion->nombre }}
                            </option>

                        @endforeach

                    </select>


                    <p
                        x-show="errores.condicion_fisica_id"
                        x-text="errores.condicion_fisica_id?.[0]"
                        class="mt-1 text-xs font-medium text-red-600"
                    ></p>

                </div>


                {{-- Serial --}}
                <div>

                    <label
                        for="serial_fabricante_incorporacion"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-slate-700
                        "
                    >
                        Serial de fabricante
                    </label>

                    <input
                        id="serial_fabricante_incorporacion"
                        type="text"
                        x-model="formulario.serial_fabricante"
                        class="input-oneshop w-full"
                        placeholder="Serial visible de la unidad"
                    >

                    <p class="mt-1 text-xs text-slate-500">
                        Puede mantenerse vacío cuando el serial no sea visible.
                    </p>

                </div>


                {{-- Observación --}}
                <div>

                    <label
                        for="observacion_incorporacion"
                        class="
                            mb-2
                            block
                            text-sm
                            font-semibold
                            text-slate-700
                        "
                    >
                        Observación
                    </label>

                    <textarea
                        id="observacion_incorporacion"
                        x-model="formulario.observacion"
                        rows="4"
                        class="input-oneshop w-full"
                        placeholder="Observaciones de la incorporación en Oruro"
                    ></textarea>

                </div>


                {{-- Error general --}}
                <div
                    x-show="errorGeneral"
                    x-text="errorGeneral"
                    class="
                        rounded-xl
                        border
                        border-red-200
                        bg-red-50
                        p-3
                        text-sm
                        font-medium
                        text-red-700
                    "
                ></div>

            </div>


            {{-- Footer --}}
            <div
                class="
                    mt-7
                    flex
                    flex-col-reverse
                    gap-3
                    sm:flex-row
                    sm:justify-end
                "
            >

                <button
                    type="button"
                    @click="modalIncorporacion = false"
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

                    <template x-if="!guardando">

                        <span class="flex items-center gap-2">
                            <x-ui.icon
                                name="check"
                                size="17"
                            />

                            Confirmar incorporación
                        </span>

                    </template>


                    <template x-if="guardando">

                        <span>
                            Procesando...
                        </span>

                    </template>

                </button>

            </div>

        </form>

    </div>

</div>