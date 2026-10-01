@php
    $checklistTecnico = \App\Models\RevisionTecnicaUnidadAdquirida::CHECKLIST;
    $checklistActual = $unidad->checklist_tecnico ?? [];

    $checklistFormulario = collect($checklistTecnico)
        ->mapWithKeys(
            fn ($etiqueta, $campo) => [
                $campo => $checklistActual[$campo] ?? '',
            ]
        )
        ->all();

    $totalChecklist = count($checklistTecnico);

    $evaluadosChecklist = collect($checklistTecnico)
        ->keys()
        ->filter(
            fn ($campo) =>
                !empty($checklistActual[$campo] ?? null)
        )
        ->count();

    $fallasChecklist = collect($checklistTecnico)
        ->keys()
        ->filter(
            fn ($campo) =>
                ($checklistActual[$campo] ?? null) ===
                \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
        );

    $pendientesChecklist = max(
        0,
        $totalChecklist - $evaluadosChecklist
    );

    $porcentajeChecklist = $totalChecklist > 0
        ? (int) round(
            ($evaluadosChecklist / $totalChecklist) * 100
        )
        : 0;

    $etiquetasResultadoRevision = [
        \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_INCOMPLETA =>
            'Incompleta',

        \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_REQUIERE_PREPARACION =>
            'Requiere preparación',

        \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA =>
            'Aprobada',
    ];

    $etiquetasEstadoPreparacion = [
        \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORIGEN =>
            'Recibida en Cochabamba',

        \App\Models\UnidadAdquirida::ESTADO_EN_REVISION =>
            'En revisión',

        \App\Models\UnidadAdquirida::ESTADO_EN_PREPARACION =>
            'En preparación',

        \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO =>
            'Lista para envío',

        \App\Models\UnidadAdquirida::ESTADO_ENVIADA =>
            'Enviada',

        \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORURO =>
            'Recibida en Oruro',

        \App\Models\UnidadAdquirida::ESTADO_INCORPORADA =>
            'Incorporada al inventario',
    ];

    $resultadoRevision =
        $etiquetasResultadoRevision[
            $unidad->resultado_revision
        ]
        ?? 'Pendiente';

    $estadoPreparacion =
        $etiquetasEstadoPreparacion[
            $unidad->estado
        ]
        ?? str_replace(
            '_',
            ' ',
            ucfirst(
                strtolower(
                    $unidad->estado
                    ?? 'Sin estado'
                )
            )
        );

    $puedeGestionarPreparacion =
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
        &&
        auth()
            ->user()
            ?->tienePermiso(
                'importacion.gestionar'
            );

    $preparacionFinalizada =
        $unidad->estado ===
        \App\Models\UnidadAdquirida::ESTADO_LISTA_ENVIO;
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
            serial_fabricante:
                @js($unidad->serial_fabricante),

            procesador:
                @js($unidad->procesador),

            generacion_procesador:
                @js($unidad->generacion_procesador),

            ram_gb:
                @js($unidad->ram_gb),

            almacenamiento_gb:
                @js($unidad->almacenamiento_gb),

            tipo_almacenamiento:
                @js($unidad->tipo_almacenamiento),

            tarjeta_grafica:
                @js($unidad->tarjeta_grafica),

            pantalla_pulgadas:
                @js($unidad->pantalla_pulgadas),

            resolucion:
                @js($unidad->resolucion),

            sistema_operativo:
                @js($unidad->sistema_operativo),

            bateria_porcentaje:
                @js($unidad->bateria_porcentaje),

            grado_final:
                @js($unidad->grado_final ?? ''),

            enciende:
                @js($unidad->enciende),

            tiene_sistema_operativo:
                @js($unidad->tiene_sistema_operativo),

            tiene_cargador:
                @js($unidad->tiene_cargador),

            requiere_servicio:
                @js((bool) $unidad->requiere_servicio),

            checklist_tecnico:
                @js($checklistFormulario),

            servicio_requerido:
                @js($unidad->servicio_requerido),

            observacion_revision:
                @js($unidad->observacion_revision)
        },

        valoresChecklist: {
            ok:
                @js(
                    \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_OK
                ),

            falla:
                @js(
                    \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                ),

            noAplica:
                @js(
                    \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA
                ),
        },

        etiquetasChecklist:
            @js($checklistTecnico),

        ultimaSugerenciaAplicada:
            '',

        completarPendientesOk() {
            Object
                .keys(
                    this
                        .revision
                        .checklist_tecnico
                )
                .forEach(
                    (campo) => {
                        if (
                            !this
                                .revision
                                .checklist_tecnico[
                                    campo
                                ]
                        ) {
                            this
                                .revision
                                .checklist_tecnico[
                                    campo
                                ] =
                                    this
                                        .valoresChecklist
                                        .ok;
                        }
                    }
                );
        },

        claseTarjetaEstado(campo) {
            const activo =
                Boolean(
                    this
                        .revision[
                            campo
                        ]
                );

            if (!activo) {
                return 'border-slate-200 bg-white hover:bg-slate-50';
            }

            if (
                campo ===
                'requiere_servicio'
            ) {
                return 'border-amber-300 bg-amber-50 ring-2 ring-amber-100';
            }

            return 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-100';
        },

        claseIndicadorEstado(campo) {
            const activo =
                Boolean(
                    this
                        .revision[
                            campo
                        ]
                );

            if (!activo) {
                return 'border-slate-300 bg-white text-transparent';
            }

            if (
                campo ===
                'requiere_servicio'
            ) {
                return 'border-amber-300 bg-amber-100 text-amber-900';
            }

            return 'border-emerald-300 bg-emerald-100 text-emerald-900';
        },

        observacionSugerida() {
            const checklist =
                this
                    .revision
                    .checklist_tecnico
                ?? {};

            const entradas =
                Object.entries(
                    checklist
                );

            const fallas =
                entradas
                    .filter(
                        ([, valor]) =>
                            valor ===
                            this
                                .valoresChecklist
                                .falla
                    )
                    .map(
                        ([campo]) =>
                            this
                                .etiquetasChecklist[
                                    campo
                                ]
                            ?? campo
                    );

            const noAplica =
                entradas
                    .filter(
                        ([, valor]) =>
                            valor ===
                            this
                                .valoresChecklist
                                .noAplica
                    )
                    .map(
                        ([campo]) =>
                            this
                                .etiquetasChecklist[
                                    campo
                                ]
                            ?? campo
                    );

            const pendientes =
                entradas
                    .filter(
                        ([, valor]) =>
                            !valor
                    )
                    .length;

            const todoChecklistOk =
                entradas.length > 0
                &&
                entradas.every(
                    ([, valor]) =>
                        valor ===
                        this
                            .valoresChecklist
                            .ok
                );

            const lineas = [];

            if (
                this.revision.enciende
                === false
            ) {
                lineas.push(
                    'Estado operativo: la unidad no enciende.'
                );
            }

            if (
                this
                    .revision
                    .tiene_sistema_operativo
                === false
            ) {
                lineas.push(
                    'Estado operativo: la unidad no tiene sistema operativo.'
                );
            }

            if (
                this
                    .revision
                    .tiene_cargador
                === false
            ) {
                lineas.push(
                    'Estado operativo: la unidad no cuenta con cargador.'
                );
            }

            if (
                fallas.length > 0
            ) {
                lineas.push(
                    `Fallas detectadas: ${fallas.join(', ')}.`
                );
            }

            if (
                noAplica.length > 0
            ) {
                lineas.push(
                    `No aplica: ${noAplica.join(', ')}.`
                );
            }

            if (
                this
                    .revision
                    .requiere_servicio
            ) {
                const servicio =
                    (
                        this
                            .revision
                            .servicio_requerido
                        ?? ''
                    )
                    .trim();

                lineas.push(
                    servicio
                        ? `Preparación requerida: ${servicio}.`
                        : 'La unidad requiere preparación adicional.'
                );
            }

            if (
                lineas.length === 0
                &&
                todoChecklistOk
            ) {
                return 'Sin observaciones relevantes. Checklist completado sin fallas.';
            }

            if (
                lineas.length === 0
                &&
                pendientes > 0
            ) {
                return `Sin observaciones automáticas por el momento. Quedan ${pendientes} prueba${pendientes === 1 ? '' : 's'} pendiente${pendientes === 1 ? '' : 's'} de evaluación.`;
            }

            if (
                lineas.length === 0
            ) {
                return 'Sin observaciones relevantes.';
            }

            if (
                pendientes > 0
            ) {
                lineas.push(
                    `Checklist en proceso: ${pendientes} prueba${pendientes === 1 ? '' : 's'} pendiente${pendientes === 1 ? '' : 's'}.`
                );
            }

            return lineas.join(' ');
        },

        usarObservacionSugerida() {
            const nuevaSugerencia =
                this
                    .observacionSugerida();

            const actual =
                (
                    this
                        .revision
                        .observacion_revision
                    ?? ''
                )
                .trim();

            if (
                this
                    .ultimaSugerenciaAplicada
                &&
                actual.includes(
                    this
                        .ultimaSugerenciaAplicada
                )
            ) {
                this
                    .revision
                    .observacion_revision =
                    actual.replace(
                        this
                            .ultimaSugerenciaAplicada,
                        nuevaSugerencia
                    );
            } else if (
                actual !== ''
            ) {
                this
                    .revision
                    .observacion_revision =
                    `${actual}\n\n${nuevaSugerencia}`;
            } else {
                this
                    .revision
                    .observacion_revision =
                    nuevaSugerencia;
            }

            this
                .ultimaSugerenciaAplicada =
                nuevaSugerencia;
        },

        prepararPayload(
            accion = 'FINALIZAR'
        ) {
            const payload =
                JSON.parse(
                    JSON.stringify(
                        this.revision
                    )
                );

            payload.accion =
                accion;

            [
                'enciende',
                'tiene_sistema_operativo',
                'tiene_cargador'
            ].forEach(
                (campo) => {
                    if (
                        payload[campo] === ''
                        ||
                        payload[campo] === null
                    ) {
                        payload[campo] =
                            null;

                        return;
                    }

                    if (
                        typeof payload[campo]
                        === 'boolean'
                    ) {
                        return;
                    }

                    payload[campo] =
                        [
                            '1',
                            1,
                            'true'
                        ]
                        .includes(
                            payload[campo]
                        );
                }
            );

            if (
                payload.grado_final
                === ''
            ) {
                payload.grado_final =
                    null;
            }

            if (
                payload.bateria_porcentaje
                === ''
            ) {
                payload
                    .bateria_porcentaje =
                    null;
            }

            Object
                .keys(
                    payload
                        .checklist_tecnico
                )
                .forEach(
                    (campo) => {
                        if (
                            payload
                                .checklist_tecnico[
                                    campo
                                ]
                            === ''
                        ) {
                            payload
                                .checklist_tecnico[
                                    campo
                                ] = null;
                        }
                    }
                );

            return payload;
        },

        async guardarRevision(
            accion = 'FINALIZAR'
        ) {
            if (
                this.guardandoRevision
            ) {
                return;
            }

            this.guardandoRevision =
                true;

            this.erroresRevision =
                {};

            this.errorRevision =
                '';

            try {
                const respuesta =
                    await window
                        .axios
                        .patch(
                            @js(
                                route(
                                    'unidades-adquiridas.revision',
                                    $unidad
                                )
                            ),
                            this.prepararPayload(
                                accion
                            ),
                            {
                                headers: {
                                    'Accept':
                                        'application/json'
                                }
                            }
                        );

                if (
                    respuesta.data?.ok
                ) {
                    window
                        .location
                        .reload();

                    return;
                }

                this.errorRevision =
                    'No fue posible guardar la revisión.';
            } catch (error) {
                if (
                    error.response
                        ?.status === 422
                ) {
                    this.erroresRevision =
                        error
                            .response
                            .data
                            .errors
                        ?? {};

                    this.errorRevision =
                        error
                            .response
                            .data
                            .message
                        ?? 'Revise los datos ingresados.';
                } else {
                    this.errorRevision =
                        error
                            .response
                            ?.data
                            ?.message
                        ?? 'Ocurrió un error al guardar la revisión.';
                }
            } finally {
                this.guardandoRevision =
                    false;
            }
        },

        async reabrirPreparacion() {
            if (
                this.guardandoReapertura
            ) {
                return;
            }

            this.guardandoReapertura =
                true;

            this.errorReapertura =
                '';

            try {
                const respuesta =
                    await window
                        .axios
                        .patch(
                            @js(
                                route(
                                    'unidades-adquiridas.reabrir-preparacion',
                                    $unidad
                                )
                            ),
                            {
                                motivo:
                                    this
                                        .motivoReapertura
                            },
                            {
                                headers: {
                                    'Accept':
                                        'application/json'
                                }
                            }
                        );

                if (
                    respuesta.data?.ok
                ) {
                    window
                        .location
                        .reload();

                    return;
                }

                this.errorReapertura =
                    'No fue posible reabrir la preparación.';
            } catch (error) {
                this.errorReapertura =
                    error
                        .response
                        ?.data
                        ?.message
                    ??
                    error
                        .response
                        ?.data
                        ?.errors
                        ?.motivo
                        ?.[0]
                    ??
                    'Ocurrió un error al reabrir la preparación.';
            } finally {
                this.guardandoReapertura =
                    false;
            }
        }
    }"
    @keydown.escape.window="
        if (
            modalRevision
            &&
            !guardandoRevision
        ) {
            modalRevision = false;
        }

        if (
            modalReapertura
            &&
            !guardandoReapertura
        ) {
            modalReapertura = false;
        }
    "
    class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm"
>
    {{-- CABECERA --}}
    <div class="flex flex-col gap-4 border-b border-blue-100 bg-gradient-to-r from-blue-50 via-oneshop-soft to-white px-6 py-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-blue-200 bg-white text-oneshop-primary shadow-sm">
                <x-ui.icon
                    name="wrench"
                    size="20"
                />
            </div>

            <div>
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                    Cochabamba · predespacho
                </p>

                <h3 class="mt-0.5 text-lg font-bold text-slate-950">
                    Preparación y revisión técnica
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Verificación funcional y técnica antes del envío a Oruro.
                </p>
            </div>
        </div>

        @if($puedeGestionarPreparacion)
            <div class="shrink-0">
                @if($preparacionFinalizada)
                    <button
                        type="button"
                        @click="
                            modalReapertura = true;
                            errorReapertura = '';
                            motivoReapertura = '';
                        "
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-900 transition hover:bg-amber-100"
                    >
                        <x-ui.icon
                            name="wrench"
                            size="17"
                        />

                        Reabrir preparación
                    </button>
                @else
                    <button
                        type="button"
                        @click="modalRevision = true"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-4 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100"
                    >
                        <x-ui.icon
                            name="edit"
                            size="17"
                        />

                        {{
                            $evaluadosChecklist > 0
                                ? 'Continuar checklist'
                                : 'Completar checklist'
                        }}
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- ESTADO DE LA ETAPA --}}
    <div class="grid gap-3 border-b border-slate-200 px-6 py-5 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                Estado
            </p>

            <p class="mt-1.5 text-sm font-bold text-slate-900">
                {{ $estadoPreparacion }}
            </p>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                Resultado revisión
            </p>

            <p class="mt-1.5 text-sm font-bold text-slate-900">
                {{ $resultadoRevision }}
            </p>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                Grado final
            </p>

            <p class="mt-1.5 text-sm font-bold text-slate-900">
                {{ $unidad->grado_final ?? 'Pendiente' }}
            </p>
        </article>

        <article class="rounded-xl border border-blue-200 bg-blue-50/60 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-oneshop-primary">
                Batería
            </p>

            <p class="mt-1.5 text-sm font-bold text-oneshop-dark">
                {{
                    is_null(
                        $unidad
                            ->bateria_porcentaje
                    )
                        ? 'No registrada'
                        : $unidad
                            ->bateria_porcentaje
                            . '%'
                }}
            </p>
        </article>
    </div>

    <div class="space-y-6 p-6">
        {{-- INDICADORES RÁPIDOS --}}
        <section>
            <div class="mb-3">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                    Estado operativo
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Lectura rápida de los puntos principales de la unidad.
                </p>
            </div>

            <div class="grid gap-3 md:grid-cols-3">
                @foreach([
                    [
                        'enciende',
                        'Encendido'
                    ],
                    [
                        'tiene_sistema_operativo',
                        'Sistema operativo'
                    ],
                    [
                        'tiene_cargador',
                        'Cargador disponible'
                    ],
                ] as [$campo, $etiqueta])
                    @php
                        $valor =
                            $unidad->{$campo};
                    @endphp

                    <div
                        @class([
                            'rounded-xl border p-4',
                            'border-slate-200 bg-slate-50/70' =>
                                is_null($valor),

                            'border-emerald-200 bg-emerald-50' =>
                                !is_null($valor)
                                && $valor,

                            'border-red-200 bg-red-50' =>
                                !is_null($valor)
                                && !$valor,
                        ])
                    >
                        <p class="text-sm font-semibold text-slate-600">
                            {{ $etiqueta }}
                        </p>

                        <p
                            @class([
                                'mt-1.5 text-sm font-black',
                                'text-slate-700' =>
                                    is_null($valor),

                                'text-emerald-800' =>
                                    !is_null($valor)
                                    && $valor,

                                'text-red-800' =>
                                    !is_null($valor)
                                    && !$valor,
                            ])
                        >
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
        </section>

        {{-- CHECKLIST --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200">
            <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50/70 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.12em] text-oneshop-primary">
                        Control funcional
                    </p>

                    <h4 class="mt-1 font-bold text-slate-950">
                        Checklist funcional
                    </h4>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $evaluadosChecklist }}/{{ $totalChecklist }}
                        pruebas evaluadas.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($fallasChecklist->isNotEmpty())
                        <span class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-xs font-bold text-red-800">
                            {{ $fallasChecklist->count() }}
                            {{ $fallasChecklist->count() === 1 ? 'falla' : 'fallas' }}
                        </span>
                    @elseif(
                        $evaluadosChecklist === $totalChecklist
                        &&
                        $totalChecklist > 0
                    )
                        <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800">
                            Checklist completo
                        </span>
                    @else
                        <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-900">
                            {{ $pendientesChecklist }} pendientes
                        </span>
                    @endif

                    <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-bold text-oneshop-dark">
                        {{ $porcentajeChecklist }}%
                    </span>
                </div>
            </div>

            <div class="px-5 pt-4">
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div
                        class="h-full rounded-full bg-oneshop-primary"
                        style="width: {{ $porcentajeChecklist }}%;"
                    ></div>
                </div>
            </div>

            <div class="grid gap-2 p-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($checklistTecnico as $campo => $etiqueta)
                    @php
                        $valor =
                            $checklistActual[
                                $campo
                            ]
                            ?? null;
                    @endphp

                    <div
                        @class([
                            'flex items-center justify-between gap-3 rounded-xl border px-3 py-3',
                            'border-emerald-100 bg-emerald-50/60' =>
                                $valor ===
                                \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_OK,

                            'border-red-100 bg-red-50/60' =>
                                $valor ===
                                \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA,

                            'border-slate-200 bg-slate-50/70' =>
                                $valor ===
                                \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA
                                ||
                                empty($valor),
                        ])
                    >
                        <span class="text-sm font-semibold text-slate-700">
                            {{ $etiqueta }}
                        </span>

                        @if(
                            $valor ===
                            \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_OK
                        )
                            <span class="shrink-0 text-xs font-black text-emerald-800">
                                OK
                            </span>
                        @elseif(
                            $valor ===
                            \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                        )
                            <span class="shrink-0 text-xs font-black text-red-800">
                                FALLA
                            </span>
                        @elseif(
                            $valor ===
                            \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_NO_APLICA
                        )
                            <span class="shrink-0 text-xs font-black text-slate-600">
                                N/A
                            </span>
                        @else
                            <span class="shrink-0 text-xs font-bold text-slate-400">
                                —
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>

        {{-- SERVICIO PENDIENTE --}}
        @if(
            $unidad->requiere_servicio
            ||
            $unidad->servicio_requerido
        )
            <section class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-amber-200 bg-white font-black text-amber-800">
                        !
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-wide text-amber-800">
                            Preparación pendiente
                        </p>

                        <p class="mt-1 text-sm font-semibold leading-6 text-amber-950">
                            {{
                                $unidad
                                    ->servicio_requerido
                                ??
                                'La unidad requiere preparación adicional.'
                            }}
                        </p>
                    </div>
                </div>
            </section>
        @endif

        {{-- OBSERVACIONES --}}
        <section class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">
                Observaciones de revisión
            </p>

            <p class="mt-2 text-sm leading-6 text-slate-700">
                {{
                    $unidad
                        ->observacion_revision
                    ??
                    'Sin observaciones registradas.'
                }}
            </p>
        </section>

        {{-- HISTORIAL --}}
        @if(
            $unidad
                ->revisionesTecnicas
                ->count()
        )
            <section class="border-t border-slate-200 pt-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">
                            Auditoría
                        </p>

                        <h4 class="mt-1 font-bold text-slate-950">
                            Historial de revisiones
                        </h4>
                    </div>

                    <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-600">
                        {{
                            $unidad
                                ->revisionesTecnicas
                                ->count()
                        }}
                        {{
                            $unidad
                                ->revisionesTecnicas
                                ->count() === 1
                                ? 'revisión'
                                : 'revisiones'
                        }}
                    </span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach(
                        $unidad
                            ->revisionesTecnicas
                            ->sortByDesc(
                                'fecha_revision'
                            )
                            ->take(5)
                        as $revisionHistorica
                    )
                        @php
                            $fallasHistoricas =
                                collect(
                                    $revisionHistorica
                                        ->checklist_tecnico
                                    ?? []
                                )
                                ->filter(
                                    fn ($valor) =>
                                        $valor ===
                                        \App\Models\RevisionTecnicaUnidadAdquirida::CHECK_FALLA
                                )
                                ->keys();
                        @endphp

                        <article class="rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">
                                        {{
                                            $etiquetasResultadoRevision[
                                                $revisionHistorica
                                                    ->resultado
                                            ]
                                            ??
                                            $revisionHistorica
                                                ->resultado
                                        }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{
                                            $revisionHistorica
                                                ->fecha_revision
                                                ?->format(
                                                    'd/m/Y H:i'
                                                )
                                        }}

                                        @if(
                                            $revisionHistorica
                                                ->usuario
                                        )
                                            ·
                                            {{
                                                $revisionHistorica
                                                    ->usuario
                                                    ->name
                                            }}
                                        @endif
                                    </p>
                                </div>

                                @if(
                                    $revisionHistorica
                                        ->grado_final
                                )
                                    <span class="w-fit rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-bold text-slate-700">
                                        Grado
                                        {{
                                            $revisionHistorica
                                                ->grado_final
                                        }}
                                    </span>
                                @endif
                            </div>

                            @if(
                                $fallasHistoricas
                                    ->isNotEmpty()
                            )
                                <p class="mt-3 rounded-lg border border-red-100 bg-red-50 px-3 py-2 text-sm text-red-800">
                                    <strong>Fallas:</strong>
                                    {{
                                        $fallasHistoricas
                                            ->map(
                                                fn ($campo) =>
                                                    $checklistTecnico[
                                                        $campo
                                                    ]
                                                    ??
                                                    $campo
                                            )
                                            ->implode(', ')
                                    }}
                                </p>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- MODAL REAPERTURA --}}
    <div
        x-cloak
        x-show="modalReapertura"
        x-transition.opacity
        class="fixed inset-0 z-[110] flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="absolute inset-0 bg-slate-950/50 backdrop-blur-sm"
            @click="
                if (
                    !guardandoReapertura
                ) {
                    modalReapertura = false;
                }
            "
        ></div>

        <div
            x-show="modalReapertura"
            x-transition
            @click.stop
            class="relative z-10 w-full max-w-lg overflow-hidden rounded-2xl border border-amber-100 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-amber-100 bg-gradient-to-r from-amber-50 via-white to-white px-6 py-5">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-amber-200 bg-white text-amber-800">
                        <x-ui.icon
                            name="wrench"
                            size="18"
                        />
                    </div>

                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-amber-800">
                            Corrección posterior
                        </p>

                        <h3 class="mt-0.5 text-lg font-bold text-slate-950">
                            Reabrir preparación
                        </h3>

                        <p class="mt-1 text-sm leading-5 text-slate-500">
                            La unidad dejará de estar lista para envío y volverá a preparación en Cochabamba.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="modalReapertura = false"
                    :disabled="guardandoReapertura"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 transition hover:bg-amber-100 hover:text-slate-900 disabled:opacity-50"
                    aria-label="Cerrar"
                >
                    <x-ui.icon
                        name="x"
                        size="18"
                    />
                </button>
            </div>

            <div class="p-6">
                <label class="mb-2 block text-sm font-bold text-slate-700">
                    Motivo de reapertura
                    <span class="text-red-700">*</span>
                </label>

                <textarea
                    x-model="motivoReapertura"
                    rows="4"
                    maxlength="2000"
                    class="input-oneshop w-full"
                    placeholder="Ej.: al embalar se detectó que la batería dejó de cargar."
                ></textarea>

                <p class="mt-2 text-xs leading-5 text-slate-500">
                    El motivo quedará registrado en la trazabilidad de la unidad.
                </p>

                <div
                    x-show="errorReapertura"
                    x-text="errorReapertura"
                    class="mt-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800"
                ></div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-amber-100 bg-amber-50/50 px-6 py-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    @click="modalReapertura = false"
                    :disabled="guardandoReapertura"
                    class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-60"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    @click="reabrirPreparacion()"
                    :disabled="
                        guardandoReapertura
                        ||
                        !motivoReapertura.trim()
                    "
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-300 bg-amber-50 px-5 py-2.5 text-sm font-bold text-amber-900 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    <x-ui.icon
                        name="wrench"
                        size="17"
                    />

                    <span
                        x-text="
                            guardandoReapertura
                                ? 'Reabriendo...'
                                : 'Reabrir preparación'
                        "
                    ></span>
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
