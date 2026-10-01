@php
    $intervencionesUnidad = $unidad
        ->intervenciones
        ->sortByDesc('fecha_inicio')
        ->values();

    $cantidadIntervenciones =
        $intervencionesUnidad
            ->count();

    $cantidadServicios =
        $intervencionesUnidad
            ->where(
                'tipo',
                \App\Models\IntervencionUnidadAdquirida::TIPO_SERVICIO
            )
            ->count();

    $cantidadComponentes =
        $intervencionesUnidad
            ->where(
                'tipo',
                \App\Models\IntervencionUnidadAdquirida::TIPO_COMPONENTE
            )
            ->count();

    $totalIntervencionesBob =
        $intervencionesUnidad
            ->sum(
                fn ($intervencion) =>
                    (float)
                    (
                        $intervencion
                            ->monto_bob
                        ?? 0
                    )
            );

    $puedeRegistrarIntervencion =
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
@endphp

<div
    x-data="intervencionesUnidad"
    @keydown.escape.window="
        if (
            modalIntervencion
            &&
            !guardando
        ) {
            modalIntervencion = false;
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
                    Trabajo realizado
                </p>

                <h3 class="mt-0.5 text-lg font-bold text-slate-950">
                    Intervenciones de preparación
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Componentes y servicios registrados antes del envío a Oruro.
                </p>
            </div>
        </div>

        @if($puedeRegistrarIntervencion)
            <button
                type="button"
                @click="abrirModal()"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-4 py-2.5 text-sm font-bold text-oneshop-dark shadow-sm transition hover:bg-blue-100"
            >
                <x-ui.icon
                    name="plus"
                    size="17"
                />

                Registrar intervención
            </button>
        @endif
    </div>

    {{-- RESUMEN --}}
    <div class="grid gap-3 border-b border-slate-200 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                Total
            </p>

            <p class="mt-1 text-lg font-black text-slate-900">
                {{ $cantidadIntervenciones }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                Componentes
            </p>

            <p class="mt-1 text-lg font-black text-slate-900">
                {{ $cantidadComponentes }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-3">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-400">
                Servicios
            </p>

            <p class="mt-1 text-lg font-black text-slate-900">
                {{ $cantidadServicios }}
            </p>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-3">
            <p class="text-xs font-bold uppercase tracking-wide text-oneshop-primary">
                Costo acumulado
            </p>

            <p class="mt-1 text-lg font-black text-oneshop-dark">
                Bs
                {{
                    number_format(
                        $totalIntervencionesBob,
                        2
                    )
                }}
            </p>
        </div>
    </div>

    {{-- HISTORIAL --}}
    <div class="p-6">
        @forelse(
            $intervencionesUnidad
            as $intervencion
        )
            <article
                @class([
                    'relative border-l-2 pb-6 pl-6 last:border-transparent last:pb-0',
                    'border-blue-200' =>
                        !$loop->last,

                    'border-transparent' =>
                        $loop->last,
                ])
            >
                <span class="absolute -left-[7px] top-5 h-3 w-3 rounded-full border-2 border-white bg-oneshop-primary shadow"></span>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-blue-200 hover:bg-blue-50/20">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if(
                                    $intervencion->tipo ===
                                    \App\Models\IntervencionUnidadAdquirida::TIPO_COMPONENTE
                                )
                                    <span class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-oneshop-dark">
                                        Componente
                                    </span>
                                @else
                                    <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-900">
                                        Servicio
                                    </span>
                                @endif

                                @if(
                                    $intervencion->origen_componente ===
                                    \App\Models\IntervencionUnidadAdquirida::ORIGEN_STOCK
                                )
                                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-700">
                                        Desde stock
                                    </span>
                                @elseif(
                                    $intervencion->origen_componente ===
                                    \App\Models\IntervencionUnidadAdquirida::ORIGEN_COMPRA_EXTERNA
                                )
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                        Compra externa
                                    </span>
                                @endif
                            </div>

                            <h4 class="mt-3 font-bold text-slate-950">
                                {{
                                    $intervencion
                                        ->descripcion
                                }}
                            </h4>

                            @if(
                                $intervencion
                                    ->producto
                            )
                                <p class="mt-1 text-sm text-slate-600">
                                    {{
                                        $intervencion
                                            ->producto
                                            ->nombre
                                    }}

                                    @if(
                                        $intervencion
                                            ->cantidad
                                    )
                                        · Cantidad:
                                        {{
                                            $intervencion
                                                ->cantidad
                                        }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div class="shrink-0 text-left sm:text-right">
                            <p class="text-xs font-semibold text-slate-500">
                                {{
                                    $intervencion
                                        ->fecha_inicio
                                        ?->format(
                                            'd/m/Y H:i'
                                        )
                                    ??
                                    '-'
                                }}
                            </p>

                            @if(
                                $intervencion
                                    ->monto_bob
                                !== null
                            )
                                <p class="mt-1 text-sm font-black text-slate-900">
                                    Bs
                                    {{
                                        number_format(
                                            (float)
                                            $intervencion
                                                ->monto_bob,
                                            2
                                        )
                                    }}
                                </p>
                            @elseif(
                                $intervencion
                                    ->origen_componente
                                ===
                                \App\Models\IntervencionUnidadAdquirida::ORIGEN_STOCK
                            )
                                <p class="mt-1 text-xs font-semibold text-slate-500">
                                    Asignado desde existencias
                                </p>
                            @endif
                        </div>
                    </div>

                    @if(
                        $intervencion
                            ->resultado
                    )
                        <div class="mt-4 rounded-xl border border-emerald-100 bg-emerald-50 p-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-emerald-800">
                                Resultado
                            </p>

                            <p class="mt-1 text-sm leading-5 text-emerald-900">
                                {{
                                    $intervencion
                                        ->resultado
                                }}
                            </p>
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-3 text-xs text-slate-500">
                        @if(
                            $intervencion
                                ->registradoPor
                        )
                            <span>
                                Registrado por:
                                <strong class="text-slate-700">
                                    {{
                                        $intervencion
                                            ->registradoPor
                                            ->name
                                    }}
                                </strong>
                            </span>
                        @endif

                        @if(
                            $intervencion
                                ->referencia
                        )
                            <span>
                                Referencia:
                                <strong class="text-slate-700">
                                    {{
                                        $intervencion
                                            ->referencia
                                    }}
                                </strong>
                            </span>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="py-10 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-400">
                    <x-ui.icon
                        name="wrench"
                        size="22"
                    />
                </div>

                <h4 class="mt-3 font-bold text-slate-900">
                    Sin intervenciones
                </h4>

                <p class="mx-auto mt-1 max-w-md text-sm leading-5 text-slate-500">
                    Esta unidad todavía no registra componentes ni servicios adicionales durante la preparación.
                </p>
            </div>
        @endforelse
    </div>

    @include(
        'unidades_adquiridas.partials.modal-intervencion',
        [
            'unidad' =>
                $unidad,

            'productosComponentes' =>
                $productosComponentes,

            'monedas' =>
                $monedas,
        ]
    )
</div>

<script>
document.addEventListener(
    'alpine:init',
    () => {
        Alpine.data(
            'intervencionesUnidad',
            () => ({
                modalIntervencion:
                    false,

                tipoIntervencion:
                    'externo',

                guardando:
                    false,

                errores:
                    {},

                errorGeneral:
                    '',

                monedas:
                    @js(
                        $monedas
                            ->map(
                                fn ($moneda) => [
                                    'id' =>
                                        $moneda
                                            ->id,

                                    'codigo' =>
                                        $moneda
                                            ->codigo,
                                ]
                            )
                            ->values()
                    ),

                externo: {
                    producto_id:
                        '',

                    cantidad:
                        1,

                    moneda_id:
                        '',

                    monto_origen:
                        '',

                    tipo_cambio_aplicado:
                        '',

                    fecha:
                        '',

                    descripcion:
                        '',

                    referencia:
                        '',

                    observacion:
                        '',
                },

                stock: {
                    producto_id:
                        '',

                    cantidad:
                        1,

                    fecha:
                        '',

                    descripcion:
                        '',

                    observacion:
                        '',
                },

                servicio: {
                    descripcion:
                        '',

                    fecha_inicio:
                        '',

                    fecha_fin:
                        '',

                    moneda_id:
                        '',

                    monto_origen:
                        '',

                    tipo_cambio_aplicado:
                        '',

                    resultado:
                        '',

                    referencia:
                        '',

                    observacion:
                        '',
                },

                abrirModal()
                {
                    this.tipoIntervencion =
                        'externo';

                    this.errores =
                        {};

                    this.errorGeneral =
                        '';

                    this.modalIntervencion =
                        true;
                },

                seleccionarTipo(tipo)
                {
                    this.tipoIntervencion =
                        tipo;

                    this.errores =
                        {};

                    this.errorGeneral =
                        '';
                },

                codigoMoneda(id)
                {
                    const moneda =
                        this.monedas.find(
                            moneda =>
                                String(
                                    moneda.id
                                )
                                ===
                                String(id)
                        );

                    return moneda?.codigo
                        ?? '';
                },

                normalizarCosto(datos)
                {
                    const payload = {
                        ...datos
                    };

                    if (
                        payload.monto_origen
                        === ''
                        ||
                        payload.monto_origen
                        === null
                    ) {
                        payload.monto_origen =
                            null;

                        payload.moneda_id =
                            null;

                        payload.tipo_cambio_aplicado =
                            null;

                        return payload;
                    }

                    if (
                        this.codigoMoneda(
                            payload.moneda_id
                        ) === 'BOB'
                    ) {
                        payload.tipo_cambio_aplicado =
                            null;
                    }

                    return payload;
                },

                async guardar()
                {
                    if (
                        this.guardando
                    ) {
                        return;
                    }

                    this.guardando =
                        true;

                    this.errores =
                        {};

                    this.errorGeneral =
                        '';

                    let url = '';
                    let payload = {};

                    if (
                        this.tipoIntervencion
                        === 'externo'
                    ) {
                        url =
                            @js(
                                route(
                                    'unidades-adquiridas.intervenciones.componente-externo',
                                    $unidad
                                )
                            );

                        payload =
                            this.normalizarCosto(
                                this.externo
                            );
                    } else if (
                        this.tipoIntervencion
                        === 'stock'
                    ) {
                        url =
                            @js(
                                route(
                                    'unidades-adquiridas.intervenciones.componente-stock',
                                    $unidad
                                )
                            );

                        payload = {
                            ...this.stock
                        };
                    } else {
                        url =
                            @js(
                                route(
                                    'unidades-adquiridas.intervenciones.servicio',
                                    $unidad
                                )
                            );

                        payload =
                            this.normalizarCosto(
                                this.servicio
                            );
                    }

                    try {
                        const respuesta =
                            await window
                                .axios
                                .post(
                                    url,
                                    payload,
                                    {
                                        headers: {
                                            'Accept':
                                                'application/json'
                                        }
                                    }
                                );

                        if (
                            respuesta
                                .data
                                ?.ok
                        ) {
                            window
                                .location
                                .reload();

                            return;
                        }

                        this.errorGeneral =
                            'No fue posible registrar la intervención.';
                    } catch (error) {
                        if (
                            error
                                .response
                                ?.status
                            === 422
                        ) {
                            this.errores =
                                error
                                    .response
                                    .data
                                    .errors
                                ?? {};

                            this.errorGeneral =
                                error
                                    .response
                                    .data
                                    .message
                                ?? 'Revise los datos ingresados.';
                        } else {
                            this.errorGeneral =
                                error
                                    .response
                                    ?.data
                                    ?.message
                                ?? 'Ocurrió un error al registrar la intervención.';
                        }
                    } finally {
                        this.guardando =
                            false;
                    }
                },
            })
        );
    }
);
</script>
