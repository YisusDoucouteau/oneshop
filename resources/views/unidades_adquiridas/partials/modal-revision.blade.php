<div
    x-cloak
    x-show="modalRevision"
    x-transition.opacity
    class="fixed inset-0 z-[100] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="tituloModalRevision"
>
    <div
        class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
        @click="
            if (
                !guardandoRevision
            ) {
                modalRevision = false;
            }
        "
    ></div>

    <div
        x-show="modalRevision"
        x-transition
        @click.stop
        class="relative z-10 flex max-h-[94vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-2xl"
    >
        {{-- CABECERA --}}
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5">
            <div class="flex items-start gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                    <x-ui.icon
                        name="wrench"
                        size="20"
                    />
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                        Preparación en Cochabamba
                    </p>

                    <h3
                        id="tituloModalRevision"
                        class="mt-0.5 text-xl font-bold text-slate-950"
                    >
                        Revisión preliminar
                    </h3>

                    <p class="mt-1 text-sm text-slate-500">
                        Verifica la condición técnica antes del despacho a Oruro.
                    </p>
                </div>
            </div>

            <button
                type="button"
                @click="modalRevision = false"
                :disabled="guardandoRevision"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-blue-100 hover:text-slate-900 disabled:opacity-50"
                aria-label="Cerrar"
            >
                <x-ui.icon
                    name="x"
                    size="18"
                />
            </button>
        </div>

        <form
            @submit.prevent="guardarRevision('FINALIZAR')"
            class="flex min-h-0 flex-1 flex-col"
        >
            <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-6">
                {{-- DATOS TÉCNICOS --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="border-b border-slate-200 bg-slate-50/70 px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                            Identificación técnica
                        </p>

                        <h4 class="mt-1 font-bold text-slate-950">
                            Datos observados
                        </h4>

                        <p class="mt-1 text-sm text-slate-500">
                            Corrige aquí cualquier diferencia detectada durante la preparación.
                        </p>
                    </div>

                    <div class="grid gap-5 p-5 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Serial fabricante
                            </label>

                            <input
                                type="text"
                                x-model="revision.serial_fabricante"
                                class="input-oneshop w-full"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Procesador
                            </label>

                            <input
                                type="text"
                                x-model="revision.procesador"
                                class="input-oneshop w-full"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Generación
                            </label>

                            <input
                                type="text"
                                x-model="revision.generacion_procesador"
                                class="input-oneshop w-full"
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
                                    x-model="revision.ram_gb"
                                    class="input-oneshop w-full pr-12"
                                >

                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">
                                    GB
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Almacenamiento
                            </label>

                            <div class="relative">
                                <input
                                    type="number"
                                    min="0"
                                    x-model="revision.almacenamiento_gb"
                                    class="input-oneshop w-full pr-12"
                                >

                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">
                                    GB
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Tipo de almacenamiento
                            </label>

                            <select
                                x-model="revision.tipo_almacenamiento"
                                class="input-oneshop w-full"
                            >
                                <option value="">
                                    No especificado
                                </option>

                                <option value="SSD">
                                    SSD
                                </option>

                                <option value="HDD">
                                    HDD
                                </option>

                                <option value="NVME">
                                    NVMe
                                </option>

                                <option value="EMMC">
                                    eMMC
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Tarjeta gráfica
                            </label>

                            <input
                                type="text"
                                x-model="revision.tarjeta_grafica"
                                class="input-oneshop w-full"
                            >
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Pantalla
                            </label>

                            <div class="relative">
                                <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    x-model="revision.pantalla_pulgadas"
                                    class="input-oneshop w-full pr-12"
                                >

                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-bold text-slate-400">
                                    pulg.
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Resolución
                            </label>

                            <input
                                type="text"
                                x-model="revision.resolucion"
                                class="input-oneshop w-full"
                                placeholder="1920x1080"
                            >
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Sistema operativo
                            </label>

                            <input
                                type="text"
                                x-model="revision.sistema_operativo"
                                class="input-oneshop w-full"
                                placeholder="Ej. Windows 11 Pro"
                            >
                        </div>
                    </div>
                </section>

                {{-- ESTADO OPERATIVO --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="border-b border-slate-200 px-5 py-4">
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                            Condición
                        </p>

                        <h4 class="mt-1 font-bold text-slate-950">
                            Estado operativo
                        </h4>

                        <p class="mt-1 text-sm text-slate-500">
                            Las condiciones confirmadas se resaltan automáticamente para facilitar la lectura.
                        </p>
                    </div>

                    <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach([
                            [
                                'enciende',
                                'La unidad enciende'
                            ],
                            [
                                'tiene_sistema_operativo',
                                'Tiene sistema operativo'
                            ],
                            [
                                'tiene_cargador',
                                'Cargador disponible con la unidad'
                            ],
                            [
                                'requiere_servicio',
                                'Requiere preparación adicional'
                            ],
                        ] as [$campo, $etiqueta])
                            <label class="cursor-pointer">
                                <input
                                    type="checkbox"
                                    x-model="revision.{{ $campo }}"
                                    @if(
                                        $campo ===
                                        'requiere_servicio'
                                    )
                                        @change="
                                            if (
                                                !revision.requiere_servicio
                                            ) {
                                                revision.servicio_requerido = '';
                                            }
                                        "
                                    @endif
                                    class="sr-only"
                                >

                                <span
                                    :class="claseTarjetaEstado('{{ $campo }}')"
                                    class="flex min-h-[88px] items-start gap-3 rounded-xl border p-4 transition"
                                >
                                    <span
                                        :class="claseIndicadorEstado('{{ $campo }}')"
                                        class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded border text-xs font-black transition"
                                    >
                                        <span
                                            x-show="revision.{{ $campo }}"
                                            x-transition.opacity
                                        >
                                            ✓
                                        </span>
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold leading-5 text-slate-700">
                                            {{ $etiqueta }}
                                        </span>

                                        <span
                                            x-show="revision.{{ $campo }}"
                                            class="mt-1 block text-xs font-bold"
                                            :class="
                                                '{{ $campo }}' === 'requiere_servicio'
                                                    ? 'text-amber-800'
                                                    : 'text-emerald-800'
                                            "
                                            x-text="
                                                '{{ $campo }}' === 'requiere_servicio'
                                                    ? 'Requiere atención'
                                                    : 'Confirmado'
                                            "
                                        ></span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <div class="border-t border-slate-100 px-5 py-3">
                        <p class="text-xs leading-5 text-slate-500">
                            El cargador se registra como dato físico. Una unidad puede quedar lista para envío sin cargador si la prueba de Carga y batería del checklist fue aprobada.
                        </p>
                    </div>
                </section>

                {{-- RESULTADO --}}
                <section class="rounded-2xl border border-blue-100 bg-blue-50/30 p-5">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                            Resultado técnico
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Registra la clasificación final y el estado medido de la batería.
                        </p>
                    </div>

                    <div class="mt-4 grid gap-5 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Grado final
                            </label>

                            <select
                                x-model="revision.grado_final"
                                class="input-oneshop w-full"
                            >
                                <option value="">
                                    Pendiente
                                </option>

                                <option value="A">
                                    Grado A
                                </option>

                                <option value="B">
                                    Grado B
                                </option>

                                <option value="C">
                                    Grado C
                                </option>
                            </select>

                            <p
                                x-show="erroresRevision.grado_final"
                                x-text="erroresRevision.grado_final?.[0]"
                                class="mt-1 text-xs font-semibold text-red-700"
                            ></p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">
                                Batería (%)
                            </label>

                            <input
                                type="number"
                                min="0"
                                max="100"
                                x-model="revision.bateria_porcentaje"
                                class="input-oneshop w-full"
                                placeholder="Ej. 84"
                            >

                            <p class="mt-1 text-xs text-slate-500">
                                Puede quedar vacío si no fue posible medirla.
                            </p>

                            <p
                                x-show="erroresRevision.bateria_porcentaje"
                                x-text="erroresRevision.bateria_porcentaje?.[0]"
                                class="mt-1 text-xs font-semibold text-red-700"
                            ></p>
                        </div>
                    </div>
                </section>

                {{-- CHECKLIST --}}
                <section class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                Pruebas
                            </p>

                            <h4 class="mt-1 font-bold text-slate-950">
                                Checklist funcional
                            </h4>

                            <p class="mt-1 text-sm text-slate-500">
                                Evalúe cada prueba como OK, Falla o No aplica. Puede guardar parcialmente como borrador.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="completarPendientesOk()"
                            class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-800 transition hover:bg-emerald-100"
                        >
                            <x-ui.icon
                                name="check"
                                size="16"
                            />

                            Marcar pendientes como OK
                        </button>
                    </div>

                    <div class="border-b border-slate-100 px-5 py-3">
                        <p class="text-sm text-slate-600">
                            <span class="font-bold text-slate-800">
                                Pendientes:
                            </span>

                            <span
                                x-text="
                                    Object
                                        .values(
                                            revision
                                                .checklist_tecnico
                                        )
                                        .filter(
                                            (valor) =>
                                                !valor
                                        )
                                        .length
                                "
                            ></span>

                            /
                            {{
                                count(
                                    \App\Models\RevisionTecnicaUnidadAdquirida::CHECKLIST
                                )
                            }}
                        </p>
                    </div>

                    <div class="grid gap-3 p-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach(
                            \App\Models\RevisionTecnicaUnidadAdquirida::CHECKLIST
                            as $campoChecklist => $etiquetaChecklist
                        )
                            <div class="rounded-xl border border-slate-200 bg-white p-4">
                                <label class="mb-2 block text-sm font-bold text-slate-800">
                                    {{ $etiquetaChecklist }}
                                </label>

                                <select
                                    x-model="revision.checklist_tecnico.{{ $campoChecklist }}"
                                    class="input-oneshop w-full"
                                >
                                    <option value="">
                                        Sin revisar
                                    </option>

                                    <option value="{{ \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_OK }}">
                                        OK
                                    </option>

                                    <option value="{{ \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA }}">
                                        Falla
                                    </option>

                                    <option value="{{ \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA }}">
                                        No aplica
                                    </option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <p
                        x-show="erroresRevision.checklist_tecnico"
                        x-text="erroresRevision.checklist_tecnico?.[0]"
                        class="px-5 pb-5 text-xs font-semibold text-red-700"
                    ></p>
                </section>

                {{-- SERVICIO --}}
                <section
                    x-show="revision.requiere_servicio"
                    x-transition
                    class="rounded-xl border border-amber-200 bg-amber-50 p-5"
                >
                    <label class="mb-2 block text-sm font-bold text-amber-950">
                        Preparación o servicio requerido
                    </label>

                    <textarea
                        x-model="revision.servicio_requerido"
                        rows="3"
                        class="input-oneshop w-full"
                        placeholder="Ej. instalar SSD, sistema operativo, conseguir cargador..."
                    ></textarea>

                    <p
                        x-show="erroresRevision.servicio_requerido"
                        x-text="erroresRevision.servicio_requerido?.[0]"
                        class="mt-1 text-xs font-semibold text-red-700"
                    ></p>
                </section>

                {{-- OBSERVACIONES --}}
                <section class="space-y-4">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                                    Resumen sugerido
                                </p>

                                <p
                                    x-text="observacionSugerida()"
                                    class="mt-2 text-sm leading-6 text-slate-700"
                                ></p>

                                <p class="mt-2 text-xs leading-5 text-slate-500">
                                    Se genera a partir del checklist, las condiciones operativas y la preparación requerida. Puedes usarlo tal cual o escribir libremente.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="usarObservacionSugerida()"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-blue-200 bg-white px-4 py-2.5 text-sm font-bold text-oneshop-dark transition hover:bg-blue-50"
                            >
                                <x-ui.icon
                                    name="file"
                                    size="16"
                                />

                                Usar resumen sugerido
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Observaciones
                        </label>

                        <textarea
                            x-model="revision.observacion_revision"
                            rows="5"
                            class="input-oneshop w-full"
                            placeholder="Resultado general, condición física u observaciones de la revisión."
                        ></textarea>

                        <p class="mt-1.5 text-xs leading-5 text-slate-500">
                            El resumen sugerido nunca elimina texto escrito manualmente: si ya existe una observación, se añade debajo.
                        </p>
                    </div>
                </section>

                {{-- ERROR GENERAL --}}
                <div
                    x-show="errorRevision"
                    x-text="errorRevision"
                    class="rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800"
                ></div>
            </div>

            {{-- FOOTER --}}
            <div class="flex shrink-0 flex-col-reverse gap-3 border-t border-blue-100 bg-blue-50/40 px-6 py-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    @click="modalRevision = false"
                    :disabled="guardandoRevision"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    @click="guardarRevision('BORRADOR')"
                    :disabled="guardandoRevision"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-ui.icon
                        name="file"
                        size="17"
                    />

                    Guardar borrador
                </button>

                <button
                    type="submit"
                    :disabled="guardandoRevision"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-ui.icon
                        name="check"
                        size="17"
                    />

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
