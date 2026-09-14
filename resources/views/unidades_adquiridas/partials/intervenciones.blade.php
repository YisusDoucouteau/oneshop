<div
    x-data="intervencionesUnidad"
    class="rounded-2xl border border-slate-200 bg-white shadow-oneshop"
>

    {{-- Encabezado --}}
    <div
        class="
            flex
            flex-col
            gap-4
            border-b
            border-slate-200
            p-6
            sm:flex-row
            sm:items-center
            sm:justify-between
        "
    >

        <div class="flex items-center gap-3">

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
                <x-ui.icon
                    name="wrench"
                    size="20"
                />
            </div>

            <div>

                <h3 class="text-lg font-bold text-slate-900">
                    Intervenciones de preparación
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Componentes y servicios realizados antes del envío a Oruro.
                </p>

            </div>

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
            &&
            auth()->user()?->tienePermiso('importacion.gestionar')
        )

            <button
                type="button"
                @click="abrirModal()"
                class="
                    inline-flex
                    items-center
                    justify-center
                    gap-2
                    rounded-xl
                    bg-oneshop-primary
                    px-4
                    py-2.5
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-oneshop-dark
                "
            >
                <x-ui.icon
                    name="plus"
                    size="17"
                />

                Registrar intervención
            </button>

        @endif

    </div>


    {{-- Historial --}}
    <div class="p-6">

        @forelse($unidad->intervenciones as $intervencion)

            <div
                class="
                    relative
                    border-l-2
                    border-slate-200
                    pb-7
                    pl-6
                    last:border-transparent
                    last:pb-0
                "
            >

                <div
                    class="
                        absolute
                        -left-[7px]
                        top-1
                        h-3
                        w-3
                        rounded-full
                        border-2
                        border-white
                        bg-oneshop-primary
                        shadow
                    "
                ></div>


                <div
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        bg-slate-50/60
                        p-4
                    "
                >

                    <div
                        class="
                            flex
                            flex-col
                            gap-3
                            sm:flex-row
                            sm:items-start
                            sm:justify-between
                        "
                    >

                        <div>

                            <div class="flex flex-wrap items-center gap-2">

                                @if(
                                    $intervencion->tipo ===
                                    \App\Models\IntervencionUnidadAdquirida::TIPO_COMPONENTE
                                )

                                    <x-ui.badge color="blue">
                                        Componente
                                    </x-ui.badge>

                                @else

                                    <x-ui.badge color="yellow">
                                        Servicio
                                    </x-ui.badge>

                                @endif


                                @if(
                                    $intervencion->origen_componente ===
                                    \App\Models\IntervencionUnidadAdquirida::ORIGEN_STOCK
                                )

                                    <x-ui.badge color="gray">
                                        Desde stock
                                    </x-ui.badge>

                                @elseif(
                                    $intervencion->origen_componente ===
                                    \App\Models\IntervencionUnidadAdquirida::ORIGEN_COMPRA_EXTERNA
                                )

                                    <x-ui.badge color="green">
                                        Compra externa
                                    </x-ui.badge>

                                @endif

                            </div>


                            <p class="mt-3 font-semibold text-slate-900">
                                {{ $intervencion->descripcion }}
                            </p>


                            @if($intervencion->producto)

                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $intervencion->producto->nombre }}

                                    @if($intervencion->cantidad)
                                        · Cantidad:
                                        {{ $intervencion->cantidad }}
                                    @endif
                                </p>

                            @endif

                        </div>


                        <div class="text-left sm:text-right">

                            <p class="text-xs text-slate-500">
                                {{
                                    $intervencion
                                        ->fecha_inicio
                                        ?->format('d/m/Y H:i')
                                    ??
                                    '-'
                                }}
                            </p>


                            @if($intervencion->monto_bob !== null)

                                <p class="mt-1 font-bold text-slate-900">
                                    Bs
                                    {{
                                        number_format(
                                            (float) $intervencion->monto_bob,
                                            2
                                        )
                                    }}
                                </p>

                            @elseif(
                                $intervencion->origen_componente ===
                                \App\Models\IntervencionUnidadAdquirida::ORIGEN_STOCK
                            )

                                <p class="mt-1 text-xs font-medium text-slate-500">
                                    Asignado desde existencias
                                </p>

                            @endif

                        </div>

                    </div>


                    @if($intervencion->resultado)

                        <div
                            class="
                                mt-4
                                rounded-lg
                                border
                                border-green-100
                                bg-green-50
                                p-3
                            "
                        >

                            <p class="text-xs font-semibold uppercase text-green-700">
                                Resultado
                            </p>

                            <p class="mt-1 text-sm text-green-800">
                                {{ $intervencion->resultado }}
                            </p>

                        </div>

                    @endif


                    <div
                        class="
                            mt-4
                            flex
                            flex-wrap
                            gap-x-5
                            gap-y-2
                            border-t
                            border-slate-200
                            pt-3
                            text-xs
                            text-slate-500
                        "
                    >

                        @if($intervencion->registradoPor)

                            <span>
                                Registrado por:
                                <strong class="text-slate-700">
                                    {{ $intervencion->registradoPor->name }}
                                </strong>
                            </span>

                        @endif


                        @if($intervencion->referencia)

                            <span>
                                Referencia:
                                <strong class="text-slate-700">
                                    {{ $intervencion->referencia }}
                                </strong>
                            </span>

                        @endif

                    </div>

                </div>

            </div>

        @empty

            <div class="py-8 text-center">

                <div
                    class="
                        mx-auto
                        flex
                        h-12
                        w-12
                        items-center
                        justify-center
                        rounded-xl
                        bg-slate-100
                        text-slate-400
                    "
                >
                    <x-ui.icon
                        name="wrench"
                        size="22"
                    />
                </div>

                <h4 class="mt-3 font-semibold text-slate-900">
                    Sin intervenciones
                </h4>

                <p class="mt-1 text-sm text-slate-500">
                    Esta unidad todavía no registra componentes ni servicios adicionales.
                </p>

            </div>

        @endforelse

    </div>


    @include(
        'unidades_adquiridas.partials.modal-intervencion',
        [
            'unidad' => $unidad,
            'productosComponentes' => $productosComponentes,
            'monedas' => $monedas,
        ]
    )

</div>


@script
<script>

Alpine.data(
    'intervencionesUnidad',
    () => ({

        modalIntervencion: false,

        tipoIntervencion: 'externo',

        guardando: false,

        errores: {},

        errorGeneral: '',


        monedas: @js(
            $monedas->map(
                fn ($moneda) => [
                    'id' => $moneda->id,
                    'codigo' => $moneda->codigo,
                ]
            )->values()
        ),


        externo: {
            producto_id: '',
            cantidad: 1,
            moneda_id: '',
            monto_origen: '',
            tipo_cambio_aplicado: '',
            fecha: '',
            descripcion: '',
            referencia: '',
            observacion: '',
        },


        stock: {
            producto_id: '',
            cantidad: 1,
            fecha: '',
            descripcion: '',
            observacion: '',
        },


        servicio: {
            descripcion: '',
            fecha_inicio: '',
            fecha_fin: '',
            moneda_id: '',
            monto_origen: '',
            tipo_cambio_aplicado: '',
            resultado: '',
            referencia: '',
            observacion: '',
        },


        abrirModal()
        {
            this.tipoIntervencion = 'externo';

            this.errores = {};

            this.errorGeneral = '';

            this.modalIntervencion = true;
        },


        seleccionarTipo(tipo)
        {
            this.tipoIntervencion = tipo;

            this.errores = {};

            this.errorGeneral = '';
        },


        codigoMoneda(id)
        {
            const moneda =
                this.monedas.find(
                    moneda =>
                        String(moneda.id) ===
                        String(id)
                );

            return moneda?.codigo ?? '';
        },


        normalizarCosto(datos)
        {
            const payload = {
                ...datos
            };


            if (
                payload.monto_origen === ''
                ||
                payload.monto_origen === null
            ) {

                payload.monto_origen = null;

                payload.moneda_id = null;

                payload.tipo_cambio_aplicado = null;

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
            if (this.guardando) {
                return;
            }


            this.guardando = true;

            this.errores = {};

            this.errorGeneral = '';


            let url = '';

            let payload = {};


            if (
                this.tipoIntervencion ===
                'externo'
            ) {

                url = @js(
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
                this.tipoIntervencion ===
                'stock'
            ) {

                url = @js(
                    route(
                        'unidades-adquiridas.intervenciones.componente-stock',
                        $unidad
                    )
                );

                payload = {
                    ...this.stock
                };

            } else {

                url = @js(
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
                    await window.axios.post(
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
                    respuesta.data?.ok
                ) {

                    window.location.reload();

                    return;
                }


                this.errorGeneral =
                    'No fue posible registrar la intervención.';

            } catch (error) {

                if (
                    error.response?.status ===
                    422
                ) {

                    this.errores =
                        error.response.data.errors
                        ??
                        {};

                    this.errorGeneral =
                        error.response.data.message
                        ??
                        'Revise los datos ingresados.';

                } else {

                    this.errorGeneral =
                        error.response?.data?.message
                        ??
                        'Ocurrió un error al registrar la intervención.';
                }

            } finally {

                this.guardando = false;
            }
        },

    })
);

</script>
@endscript