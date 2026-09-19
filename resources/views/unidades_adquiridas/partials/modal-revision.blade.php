<div
    x-cloak
    x-show="modalRevision"
    x-transition.opacity
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
>

    <div
        class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
        @click="!guardandoRevision && (modalRevision = false)"
    ></div>


    <div
        x-show="modalRevision"
        x-transition
        @click.stop
        class="
            relative
            z-10
            max-h-[92vh]
            w-full
            max-w-4xl
            overflow-y-auto
            rounded-2xl
            border
            border-slate-200
            bg-white
            shadow-2xl
        "
    >

        <div
            class="
                sticky
                top-0
                z-10
                flex
                items-start
                justify-between
                border-b
                border-slate-200
                bg-white
                px-6
                py-5
            "
        >

            <div class="flex items-start gap-3">

                <div
                    class="
                        flex
                        h-10
                        w-10
                        items-center
                        justify-center
                        rounded-xl
                        bg-oneshop-light
                        text-oneshop-primary
                    "
                >
                    <x-ui.icon name="wrench" size="20"/>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-900">
                        Revisión preliminar
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Revisión realizada en Cochabamba antes del despacho a Oruro.
                    </p>
                </div>

            </div>


            <button
                type="button"
                @click="modalRevision = false"
                :disabled="guardandoRevision"
                class="
                    rounded-lg
                    p-2
                    text-slate-400
                    hover:bg-slate-100
                    hover:text-slate-700
                    disabled:opacity-50
                "
            >
                <x-ui.icon name="x" size="20"/>
            </button>

        </div>


        <form
            @submit.prevent="guardarRevision('FINALIZAR')"
            class="p-6"
        >

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Serial fabricante
                    </label>

                    <input
                        type="text"
                        x-model="revision.serial_fabricante"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Procesador
                    </label>

                    <input
                        type="text"
                        x-model="revision.procesador"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Generación
                    </label>

                    <input
                        type="text"
                        x-model="revision.generacion_procesador"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        RAM (GB)
                    </label>

                    <input
                        type="number"
                        min="0"
                        x-model="revision.ram_gb"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Almacenamiento (GB)
                    </label>

                    <input
                        type="number"
                        min="0"
                        x-model="revision.almacenamiento_gb"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Tipo de almacenamiento
                    </label>

                    <select
                        x-model="revision.tipo_almacenamiento"
                        class="input-oneshop w-full"
                    >
                        <option value="">No especificado</option>
                        <option value="SSD">SSD</option>
                        <option value="HDD">HDD</option>
                        <option value="NVME">NVMe</option>
                        <option value="EMMC">eMMC</option>
                    </select>
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Tarjeta gráfica
                    </label>

                    <input
                        type="text"
                        x-model="revision.tarjeta_grafica"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Pantalla
                    </label>

                    <input
                        type="number"
                        step="0.1"
                        min="0"
                        x-model="revision.pantalla_pulgadas"
                        class="input-oneshop w-full"
                    >
                </div>


                <div>
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Resolución
                    </label>

                    <input
                        type="text"
                        x-model="revision.resolucion"
                        class="input-oneshop w-full"
                        placeholder="1920x1080"
                    >
                </div>


                <div class="lg:col-span-3">
                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Sistema operativo
                    </label>

                    <input
                        type="text"
                        x-model="revision.sistema_operativo"
                        class="input-oneshop w-full"
                    >
                </div>

            </div>


            <div class="my-6 border-t border-slate-200"></div>


            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

                @foreach([
                    ['enciende', 'La unidad enciende'],
                    ['tiene_sistema_operativo', 'Tiene sistema operativo'],
                    ['tiene_cargador', 'Tiene cargador'],
                    ['requiere_servicio', 'Requiere preparación adicional'],
                ] as [$campo, $etiqueta])

                    <label
                        class="
                            flex
                            cursor-pointer
                            items-center
                            gap-3
                            rounded-xl
                            border
                            border-slate-200
                            bg-slate-50
                            p-4
                        "
                    >

                        <input
                            type="checkbox"
                            x-model="revision.{{ $campo }}"
                            @if($campo === 'requiere_servicio')
                                @change="if (!revision.requiere_servicio) revision.servicio_requerido = ''"
                            @endif
                            class="
                                rounded
                                border-slate-300
                                text-oneshop-primary
                                focus:ring-oneshop-primary
                            "
                        >

                        <span class="text-sm font-medium text-slate-700">
                            {{ $etiqueta }}
                        </span>

                    </label>

                @endforeach

            </div>


            <div
                x-show="revision.requiere_servicio"
                class="mt-5"
            >

                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Preparación o servicio requerido
                </label>

                <textarea
                    x-model="revision.servicio_requerido"
                    rows="3"
                    class="input-oneshop w-full"
                    placeholder="Ej.: instalar SSD, sistema operativo, conseguir cargador..."
                ></textarea>

                <p
                    x-show="erroresRevision.servicio_requerido"
                    x-text="erroresRevision.servicio_requerido?.[0]"
                    class="mt-1 text-xs font-medium text-red-600"
                ></p>

            </div>


            <div class="mt-5">

                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Observaciones
                </label>

                <textarea
                    x-model="revision.observacion_revision"
                    rows="4"
                    class="input-oneshop w-full"
                ></textarea>

            </div>


            <div
                x-show="errorRevision"
                x-text="errorRevision"
                class="
                    mt-5
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
                    @click="modalRevision = false"
                    :disabled="guardandoRevision"
                    class="btn-secondary"
                >
                    Cancelar
                </button>


                <button
                    type="button"
                    @click="guardarRevision('BORRADOR')"
                    :disabled="guardandoRevision"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-ui.icon name="file" size="17"/>
                    Guardar borrador
                </button>

                <button
                    type="submit"
                    :disabled="guardandoRevision"
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
                    <x-ui.icon name="check" size="17"/>

                    <span
                        x-text="
                            guardandoRevision
                                ? 'Guardando...'
                                : 'Finalizar revisión'
                        "
                    ></span>
                </button>

            </div>

        </form>

    </div>

</div>