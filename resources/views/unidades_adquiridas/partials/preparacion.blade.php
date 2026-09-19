@php
    $checklistTecnico = \App\Models\RevisionTecnicaUnidadAdquirida::CHECKLIST;
    $checklistActual = $unidad->checklist_tecnico ?? [];
    $checklistFormulario = collect($checklistTecnico)
        ->mapWithKeys(
            fn ($etiqueta, $campo) =>
                [$campo => $checklistActual[$campo] ?? '']
        )
        ->all();

    $totalChecklist = count($checklistTecnico);
    $evaluadosChecklist = collect($checklistTecnico)
        ->keys()
        ->filter(fn ($campo) => !empty($checklistActual[$campo] ?? null))
        ->count();
    $fallasChecklist = collect($checklistTecnico)
        ->keys()
        ->filter(
            fn ($campo) =>
                ($checklistActual[$campo] ?? null) ===
                \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
        );

    $etiquetasResultadoRevision = [
        \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_INCOMPLETA => 'Incompleta',
        \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_REQUIERE_PREPARACION => 'Requiere preparación',
        \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA => 'Aprobada',
    ];
@endphp

<div
    x-data="{
        modalRevision: false,
        modalReapertura: false,
        guardandoRevision: false,
        guardandoReapertura: false,
        motivoReapertura: '',
        errorReapertura: '',
        erroresRevision: {},
        errorRevision: '',

        revision: {
            serial_fabricante: @js($unidad->serial_fabricante),
            procesador: @js($unidad->procesador),
            generacion_procesador: @js($unidad->generacion_procesador),
            ram_gb: @js($unidad->ram_gb),
            almacenamiento_gb: @js($unidad->almacenamiento_gb),
            tipo_almacenamiento: @js($unidad->tipo_almacenamiento),
            tarjeta_grafica: @js($unidad->tarjeta_grafica),
            pantalla_pulgadas: @js($unidad->pantalla_pulgadas),
            resolucion: @js($unidad->resolucion),
            sistema_operativo: @js($unidad->sistema_operativo),
            bateria_porcentaje: @js($unidad->bateria_porcentaje),
            grado_final: @js($unidad->grado_final ?? ''),

            enciende: @js($unidad->enciende),
            tiene_sistema_operativo: @js($unidad->tiene_sistema_operativo),
            tiene_cargador: @js($unidad->tiene_cargador),
            requiere_servicio: @js((bool) $unidad->requiere_servicio),

            checklist_tecnico: @js($checklistFormulario),

            servicio_requerido: @js($unidad->servicio_requerido),
            observacion_revision: @js($unidad->observacion_revision)
        },

        completarPendientesOk() {
            Object.keys(this.revision.checklist_tecnico).forEach((campo) => {
                if (!this.revision.checklist_tecnico[campo]) {
                    this.revision.checklist_tecnico[campo] = 'OK';
                }
            });
        },

        tieneFallasChecklist() {
            return Object.values(this.revision.checklist_tecnico)
                .some((valor) => valor === 'FALLA');
        },

        tieneProblemaBasico() {
            return ['enciende', 'tiene_sistema_operativo', 'tiene_cargador']
                .some((campo) => this.revision[campo] === false || this.revision[campo] === '0');
        },

        prepararPayload(accion = 'FINALIZAR') {
            const payload = JSON.parse(JSON.stringify(this.revision));
            payload.accion = accion;

            ['enciende', 'tiene_sistema_operativo', 'tiene_cargador'].forEach((campo) => {
                if (payload[campo] === '' || payload[campo] === null) {
                    payload[campo] = null;
                    return;
                }

                if (typeof payload[campo] === 'boolean') {
                    return;
                }

                payload[campo] = ['1', 1, 'true'].includes(payload[campo]);
            });

            if (payload.grado_final === '') {
                payload.grado_final = null;
            }

            if (payload.bateria_porcentaje === '') {
                payload.bateria_porcentaje = null;
            }

            Object.keys(payload.checklist_tecnico).forEach((campo) => {
                if (payload.checklist_tecnico[campo] === '') {
                    payload.checklist_tecnico[campo] = null;
                }
            });

            return payload;
        },

        async guardarRevision(accion = 'FINALIZAR') {
            if (this.guardandoRevision) {
                return;
            }

            this.guardandoRevision = true;
            this.erroresRevision = {};
            this.errorRevision = '';

            try {
                const respuesta = await window.axios.patch(
                    @js(route('unidades-adquiridas.revision', $unidad)),
                    this.prepararPayload(accion),
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (respuesta.data?.ok) {
                    window.location.reload();
                    return;
                }

                this.errorRevision = 'No fue posible guardar la revisión.';
            } catch (error) {
                if (error.response?.status === 422) {
                    this.erroresRevision = error.response.data.errors ?? {};
                    this.errorRevision =
                        error.response.data.message
                        ?? 'Revise los datos ingresados.';
                } else {
                    this.errorRevision =
                        error.response?.data?.message
                        ?? 'Ocurrió un error al guardar la revisión.';
                }
            } finally {
                this.guardandoRevision = false;
            }
        },

        async reabrirPreparacion() {
            if (this.guardandoReapertura) {
                return;
            }

            this.guardandoReapertura = true;
            this.errorReapertura = '';

            try {
                const respuesta = await window.axios.patch(
                    @js(route('unidades-adquiridas.reabrir-preparacion', $unidad)),
                    { motivo: this.motivoReapertura },
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (respuesta.data?.ok) {
                    window.location.reload();
                    return;
                }

                this.errorReapertura = 'No fue posible reabrir la preparación.';
            } catch (error) {
                this.errorReapertura =
                    error.response?.data?.message
                    ?? error.response?.data?.errors?.motivo?.[0]
                    ?? 'Ocurrió un error al reabrir la preparación.';
            } finally {
                this.guardandoReapertura = false;
            }
        }
    }"
    class="rounded-2xl bg-white p-6 shadow-oneshop"
>
    <div class="mb-5 flex flex-wrap items-start gap-3">
        <x-ui.icon name="wrench" class="mt-1 text-oneshop-primary" />

        <div>
            <h3 class="text-lg font-bold text-gray-800">
                Preparación y revisión técnica
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                Checklist funcional previo al despacho desde Cochabamba.
            </p>
        </div>

        @if(
            in_array(
                $unidad->estado,
                [
                    \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN,
                    \App\Models\UnidadAdquirida::ESTADO_EN_REVISION,
                    \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION,
                    \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO,
                ],
                true
            )
            && auth()->user()?->tienePermiso('importacion.gestionar')
        )
            <div class="ml-auto">
                @if($unidad->estado === \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO)
                    <button
                        type="button"
                        @click="modalReapertura = true; errorReapertura = ''; motivoReapertura = ''"
                        class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 transition hover:bg-amber-100"
                    >
                        <x-ui.icon name="wrench" size="17"/>
                        Reabrir preparación
                    </button>
                @else
                    <button
                        type="button"
                        @click="modalRevision = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-oneshop-primary hover:bg-oneshop-soft hover:text-oneshop-primary"
                    >
                        <x-ui.icon name="edit" size="17"/>
                        Revisar unidad
                    </button>
                @endif
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado</p>
            <p class="mt-1 font-bold text-slate-900">{{ $unidad->estado ?? 'Sin estado' }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Resultado revisión</p>
            <p class="mt-1 font-bold text-slate-900">
                {{ $etiquetasResultadoRevision[$unidad->resultado_revision] ?? 'Pendiente' }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Grado final</p>
            <p class="mt-1 font-bold text-slate-900">{{ $unidad->grado_final ?? 'Pendiente' }}</p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Batería</p>
            <p class="mt-1 font-bold text-slate-900">
                {{ is_null($unidad->bateria_porcentaje) ? 'No registrada' : $unidad->bateria_porcentaje.'%' }}
            </p>
        </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach([
            ['enciende', 'Encendido'],
            ['tiene_sistema_operativo', 'Sistema operativo'],
            ['tiene_cargador', 'Cargador'],
        ] as [$campo, $etiqueta])
            @php
                $valor = $unidad->{$campo};
            @endphp
            <div class="rounded-xl border border-slate-200 p-4">
                <p class="text-sm text-slate-500">{{ $etiqueta }}</p>
                <p class="mt-1 font-semibold {{ is_null($valor) ? 'text-slate-700' : ($valor ? 'text-green-700' : 'text-red-700') }}">
                    @if(is_null($valor))
                        No evaluado
                    @elseif($valor)
                        OK
                    @else
                        Requiere atención
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 rounded-xl border border-slate-200 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="font-bold text-slate-900">Checklist funcional</h4>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $evaluadosChecklist }}/{{ $totalChecklist }} pruebas evaluadas
                </p>
            </div>

            @if($fallasChecklist->isNotEmpty())
                <x-ui.badge color="red">
                    {{ $fallasChecklist->count() }} falla(s)
                </x-ui.badge>
            @elseif($evaluadosChecklist === $totalChecklist && $totalChecklist > 0)
                <x-ui.badge color="green">Checklist completo</x-ui.badge>
            @else
                <x-ui.badge color="yellow">Pendiente</x-ui.badge>
            @endif
        </div>

        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($checklistTecnico as $campo => $etiqueta)
                @php
                    $valor = $checklistActual[$campo] ?? null;
                @endphp
                <div class="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2">
                    <span class="text-sm text-slate-700">{{ $etiqueta }}</span>
                    <span class="text-xs font-bold {{
                        $valor === \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_OK
                            ? 'text-green-700'
                            : ($valor === \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                                ? 'text-red-700'
                                : 'text-slate-500')
                    }}">
                        @switch($valor)
                            @case(\App\Models\RevisionTecnicaUnidadAdquirida::CHECK_OK)
                                OK
                                @break
                            @case(\App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA)
                                FALLA
                                @break
                            @case(\App\Models\RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA)
                                N/A
                                @break
                            @default
                                —
                        @endswitch
                    </span>
                </div>
            @endforeach
        </div>
    </div>

    @if($unidad->requiere_servicio || $unidad->servicio_requerido)
        <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Preparación pendiente</p>
            <p class="mt-1 text-sm font-medium text-amber-900">
                {{ $unidad->servicio_requerido ?? 'La unidad requiere preparación adicional.' }}
            </p>
        </div>
    @endif

    <div class="mt-6">
        <p class="mb-2 text-sm text-gray-500">Observaciones de revisión</p>
        <div class="rounded-xl bg-gray-50 p-4 text-gray-700">
            {{ $unidad->observacion_revision ?? 'Sin observaciones registradas.' }}
        </div>
    </div>

    @if($unidad->revisionesTecnicas->count())
        <div class="mt-6 border-t border-slate-200 pt-5">
            <h4 class="font-semibold text-slate-900">Historial de revisiones</h4>

            <div class="mt-3 space-y-3">
                @foreach($unidad->revisionesTecnicas->sortByDesc('fecha_revision')->take(5) as $revisionHistorica)
                    @php
                        $fallasHistoricas = collect($revisionHistorica->checklist_tecnico ?? [])
                            ->filter(
                                fn ($valor) =>
                                    $valor === \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                            )
                            ->keys();
                    @endphp

                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold text-slate-900">
                                    {{ $etiquetasResultadoRevision[$revisionHistorica->resultado] ?? $revisionHistorica->resultado }}
                                </p>
                                <p class="mt-1 text-xs text-slate-500">
                                    {{ $revisionHistorica->fecha_revision?->format('d/m/Y H:i') }}
                                    @if($revisionHistorica->usuario)
                                        · {{ $revisionHistorica->usuario->name }}
                                    @endif
                                </p>
                            </div>

                            @if($revisionHistorica->grado_final)
                                <x-ui.badge color="gray">
                                    Grado {{ $revisionHistorica->grado_final }}
                                </x-ui.badge>
                            @endif
                        </div>

                        @if($fallasHistoricas->isNotEmpty())
                            <p class="mt-3 text-sm text-red-700">
                                Fallas:
                                {{ $fallasHistoricas->map(fn ($campo) => $checklistTecnico[$campo] ?? $campo)->implode(', ') }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div
        x-cloak
        x-show="modalReapertura"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4"
        @keydown.escape.window="if (!guardandoReapertura) modalReapertura = false"
    >
        <div
            @click.outside="if (!guardandoReapertura) modalReapertura = false"
            class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Reabrir preparación</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        La unidad dejará de estar lista para envío y volverá a preparación en Cochabamba.
                    </p>
                </div>
                <button
                    type="button"
                    @click="modalReapertura = false"
                    :disabled="guardandoReapertura"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-100"
                >
                    <x-ui.icon name="x" size="20"/>
                </button>
            </div>

            <div class="mt-5">
                <label class="mb-2 block text-sm font-semibold text-slate-700">
                    Motivo de reapertura
                </label>
                <textarea
                    x-model="motivoReapertura"
                    rows="4"
                    maxlength="2000"
                    class="input-oneshop w-full"
                    placeholder="Ej.: al embalar se detectó que la batería dejó de cargar."
                ></textarea>
                <p class="mt-2 text-xs text-slate-500">
                    El motivo quedará registrado en la trazabilidad de la unidad.
                </p>
            </div>

            <div
                x-show="errorReapertura"
                x-text="errorReapertura"
                class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700"
            ></div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="modalReapertura = false"
                    :disabled="guardandoReapertura"
                    class="btn-secondary"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    @click="reabrirPreparacion()"
                    :disabled="guardandoReapertura || !motivoReapertura.trim()"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-ui.icon name="wrench" size="17"/>
                    <span x-text="guardandoReapertura ? 'Reabriendo...' : 'Reabrir preparación'"></span>
                </button>
            </div>
        </div>
    </div>

    @include(
        'unidades_adquiridas.partials.modal-revision',
        [
            'unidad' => $unidad,
        ]
    )
</div>
