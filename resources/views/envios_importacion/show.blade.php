<x-layouts.oneshop
    title="Detalle envío | OneShop"
    page-title="Detalle de envío"
>

@php
    $colorEstado = match ($envio->estado) {
        \App\Models\EnvioImportacion::ESTADO_BORRADOR => 'gray',
        \App\Models\EnvioImportacion::ESTADO_PREPARADO => 'blue',
        \App\Models\EnvioImportacion::ESTADO_DESPACHADO => 'yellow',
        \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL => 'yellow',
        \App\Models\EnvioImportacion::ESTADO_RECIBIDO => 'green',
        \App\Models\EnvioImportacion::ESTADO_CANCELADO => 'red',
        default => 'gray',
    };

    $nombreEstado = match ($envio->estado) {
        'BORRADOR' => 'Borrador',
        'PREPARADO' => 'Preparado',
        'DESPACHADO' => 'Despachado',
        'RECIBIDO_PARCIAL' => 'Recepción parcial',
        'RECIBIDO' => 'Recibido',
        'CANCELADO' => 'Cancelado',
        default => str_replace('_', ' ', $envio->estado),
    };

    $hayPendientes =
        $envio->unidadesEnvio
            ->contains(
                fn ($detalle) =>
                    !$detalle->estaResueltaEnRecepcion()
            );
@endphp


<div
    x-data="{
        procesando: false,
        errorGeneral: '',

        modalDespacho: false,

        despacho: {
            transportista: @js($envio->transportista ?? ''),
            numero_guia: @js($envio->numero_guia ?? '')
        },

        modalRecepcion: false,
        tipoRecepcion: 'recibir',
        urlRecepcion: '',
        unidadRecepcion: '',
        observacionRecepcion: '',


        async ejecutar(
            url,
            metodo = 'post',
            datos = {}
        ) {

            if (this.procesando) {
                return;
            }

            this.procesando = true;
            this.errorGeneral = '';

            try {

                let respuesta;

                if (metodo === 'delete') {

                    respuesta =
                        await window.axios.delete(
                            url,
                            {
                                data: datos,
                                headers: {
                                    'Accept': 'application/json'
                                }
                            }
                        );

                } else {

                    respuesta =
                        await window.axios.post(
                            url,
                            datos,
                            {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            }
                        );
                }


                if (respuesta.data?.ok) {

                    window.location.reload();

                    return;
                }


                this.errorGeneral =
                    'No fue posible completar la operación.';

            } catch (error) {

                this.errorGeneral =
                    error.response?.data?.message
                    ??
                    'Ocurrió un error al procesar la operación.';

            } finally {

                this.procesando = false;
            }
        },


        abrirRecepcion(
            tipo,
            url,
            unidad
        ) {

            this.tipoRecepcion = tipo;

            this.urlRecepcion = url;

            this.unidadRecepcion = unidad;

            this.observacionRecepcion = '';

            this.errorGeneral = '';

            this.modalRecepcion = true;
        },


        async guardarRecepcion() {

            if (
                (
                    this.tipoRecepcion === 'faltante'
                    ||
                    this.tipoRecepcion === 'incidencia'
                )
                &&
                !this.observacionRecepcion.trim()
            ) {

                this.errorGeneral =
                    'Debe registrar una observación.';

                return;
            }


            await this.ejecutar(
                this.urlRecepcion,
                'post',
                {
                    observacion:
                        this.observacionRecepcion
                }
            );
        },


        async despachar() {

            await this.ejecutar(
                @js(
                    route(
                        'envios-importacion.despachar',
                        $envio
                    )
                ),
                'post',
                this.despacho
            );
        }
    }"
    class="space-y-6"
>


    {{-- ============================================================
        ERROR GENERAL
    ============================================================ --}}
    <div
        x-cloak
        x-show="errorGeneral"
        x-text="errorGeneral"
        class="
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


    @if(session('success'))

        <div
            class="
                flex
                items-center
                gap-3
                rounded-xl
                border
                border-green-200
                bg-green-50
                p-4
                text-green-800
            "
        >
            <x-ui.icon
                name="check"
                size="20"
            />

            <span class="text-sm font-medium">
                {{ session('success') }}
            </span>
        </div>

    @endif



    {{-- ============================================================
        CABECERA
    ============================================================ --}}
    <x-ui.card>

        <div
            class="
                flex
                flex-col
                gap-5
                lg:flex-row
                lg:items-start
                lg:justify-between
            "
        >

            <div>

                <div class="flex flex-wrap items-center gap-3">

                    <div
                        class="
                            flex
                            h-12
                            w-12
                            items-center
                            justify-center
                            rounded-xl
                            bg-oneshop-light
                            text-oneshop-primary
                        "
                    >
                        <x-ui.icon
                            name="truck"
                            size="24"
                        />
                    </div>


                    <div>

                        <p
                            class="
                                text-xs
                                font-semibold
                                uppercase
                                tracking-wide
                                text-slate-500
                            "
                        >
                            Envío de preinventario
                        </p>

                        <h1
                            class="
                                mt-1
                                text-2xl
                                font-bold
                                text-slate-900
                            "
                        >
                            {{ $envio->codigo }}
                        </h1>

                    </div>


                    <x-ui.badge
                        :color="$colorEstado"
                    >
                        {{ $nombreEstado }}
                    </x-ui.badge>

                </div>


                <div
                    class="
                        mt-5
                        flex
                        flex-wrap
                        items-center
                        gap-3
                        text-sm
                        text-slate-600
                    "
                >

                    <span class="font-semibold text-slate-900">
                        {{
                            $envio->almacenOrigen?->nombre
                            ?? 'Cochabamba'
                        }}
                    </span>

                    <x-ui.icon
                        name="truck"
                        size="16"
                        class="text-oneshop-primary"
                    />

                    <span class="font-semibold text-slate-900">
                        {{
                            $envio->almacenDestino?->nombre
                            ?? 'Oruro'
                        }}
                    </span>

                </div>

            </div>


            <a
                href="{{ route('envios-importacion.index') }}"
                class="btn-secondary"
            >
                Volver a envíos
            </a>

        </div>

    </x-ui.card>



    {{-- ============================================================
        INFORMACIÓN GENERAL
    ============================================================ --}}
    <div
        class="
            grid
            grid-cols-1
            gap-4
            md:grid-cols-2
            xl:grid-cols-4
        "
    >

        <x-ui.card>

            <p class="text-xs font-semibold uppercase text-slate-500">
                Transportista
            </p>

            <p class="mt-2 font-semibold text-slate-900">
                {{ $envio->transportista ?? 'No registrado' }}
            </p>

        </x-ui.card>


        <x-ui.card>

            <p class="text-xs font-semibold uppercase text-slate-500">
                Número de guía
            </p>

            <p class="mt-2 font-semibold text-slate-900">
                {{ $envio->numero_guia ?? 'No registrado' }}
            </p>

        </x-ui.card>


        <x-ui.card>

            <p class="text-xs font-semibold uppercase text-slate-500">
                Bultos
            </p>

            <p class="mt-2 text-xl font-bold text-slate-900">
                {{ $envio->cantidad_bultos ?? 1 }}
            </p>

        </x-ui.card>


        <x-ui.card>

            <p class="text-xs font-semibold uppercase text-slate-500">
                Unidades
            </p>

            <p class="mt-2 text-xl font-bold text-slate-900">
                {{ $envio->unidadesEnvio->count() }}
            </p>

        </x-ui.card>

    </div>



    {{-- ============================================================
        ACCIONES DEL ENVÍO
    ============================================================ --}}
    @if(auth()->user()?->tienePermiso('importacion.gestionar'))

        <x-ui.card>

            <div
                class="
                    flex
                    flex-col
                    gap-4
                    lg:flex-row
                    lg:items-center
                    lg:justify-between
                "
            >

                <div>

                    <h2 class="font-bold text-slate-900">
                        Operación del envío
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">

                        @if($envio->estaEnBorrador())

                            Agregue las unidades y luego confirme que el envío está preparado.

                        @elseif($envio->estaPreparado())

                            El envío está listo para ser despachado desde Cochabamba.

                        @elseif($envio->estaDespachado())

                            Las unidades se encuentran en traslado hacia Oruro.

                        @elseif(
                            $envio->estado ===
                            \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL
                        )

                            Existen unidades faltantes o con incidencias.

                        @elseif(
                            $envio->estado ===
                            \App\Models\EnvioImportacion::ESTADO_RECIBIDO
                        )

                            La recepción del envío fue completada.

                        @endif

                    </p>

                </div>


                <div class="flex flex-wrap gap-2">


                    {{-- PREPARAR --}}
                    @if($envio->estaEnBorrador())

                        <button
                            type="button"
                            @click="
                                ejecutar(
                                    @js(
                                        route(
                                            'envios-importacion.preparar',
                                            $envio
                                        )
                                    )
                                )
                            "
                            :disabled="procesando"
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-oneshop-primary
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-white
                                hover:bg-oneshop-dark
                                disabled:opacity-50
                            "
                        >
                            <x-ui.icon
                                name="check"
                                size="17"
                            />

                            Marcar preparado
                        </button>

                    @endif


                    {{-- DESPACHAR --}}
                    @if($envio->estaPreparado())

                        <button
                            type="button"
                            @click="
                                errorGeneral = '';
                                modalDespacho = true
                            "
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-oneshop-primary
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-white
                                hover:bg-oneshop-dark
                            "
                        >
                            <x-ui.icon
                                name="truck"
                                size="17"
                            />

                            Despachar a Oruro
                        </button>

                    @endif


                    {{-- CERRAR RECEPCIÓN --}}
                    @if(
                        in_array(
                            $envio->estado,
                            [
                                \App\Models\EnvioImportacion::ESTADO_DESPACHADO,
                                \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                            ],
                            true
                        )
                        &&
                        !$hayPendientes
                        &&
                        $envio->unidadesEnvio->isNotEmpty()
                    )

                        <button
                            type="button"
                            @click="
                                ejecutar(
                                    @js(
                                        route(
                                            'envios-importacion.cerrar-recepcion',
                                            $envio
                                        )
                                    )
                                )
                            "
                            :disabled="procesando"
                            class="
                                inline-flex
                                items-center
                                gap-2
                                rounded-xl
                                bg-green-600
                                px-4
                                py-2.5
                                text-sm
                                font-semibold
                                text-white
                                hover:bg-green-700
                                disabled:opacity-50
                            "
                        >
                            <x-ui.icon
                                name="check"
                                size="17"
                            />

                            Cerrar recepción
                        </button>

                    @endif

                </div>

            </div>

        </x-ui.card>

    @endif



    {{-- ============================================================
        AGREGAR UNIDADES AL BORRADOR
    ============================================================ --}}
    @if(
        $envio->estaEnBorrador()
        &&
        auth()->user()?->tienePermiso('importacion.gestionar')
    )

        <x-ui.card>

            <div class="mb-5">

                <h2 class="text-lg font-bold text-slate-900">
                    Unidades disponibles
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Solo aparecen unidades que se encuentran listas para envío en Cochabamba.
                </p>

            </div>


            @forelse($unidadesDisponibles as $unidad)

                <div
                    class="
                        flex
                        flex-col
                        gap-4
                        border-t
                        border-slate-100
                        py-4
                        first:border-t-0
                        first:pt-0
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
                                bg-slate-100
                                text-slate-500
                            "
                        >
                            <x-ui.icon
                                name="laptop"
                                size="19"
                            />
                        </div>


                        <div>

                            <p class="font-semibold text-slate-900">
                                {{
                                    $unidad->codigo_trazabilidad
                                    ?? 'Sin código'
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

                                @if(
                                    $unidad->producto?->modelo
                                    ||
                                    $unidad->modelo_equipo
                                )

                                    ·

                                    {{
                                        $unidad->producto?->modelo
                                        ??
                                        $unidad->modelo_equipo
                                    }}

                                @endif
                            </p>

                        </div>

                    </div>


                    <button
                        type="button"
                        @click="
                            ejecutar(
                                @js(
                                    route(
                                        'envios-importacion.unidades.agregar',
                                        [
                                            'envio' => $envio,
                                            'unidad' => $unidad,
                                        ]
                                    )
                                )
                            )
                        "
                        :disabled="procesando"
                        class="
                            inline-flex
                            items-center
                            justify-center
                            gap-2
                            rounded-xl
                            border
                            border-oneshop-primary
                            px-4
                            py-2
                            text-sm
                            font-semibold
                            text-oneshop-primary
                            transition
                            hover:bg-oneshop-soft
                            disabled:opacity-50
                        "
                    >
                        <x-ui.icon
                            name="plus"
                            size="16"
                        />

                        Agregar
                    </button>

                </div>

            @empty

                <div
                    class="
                        rounded-xl
                        border
                        border-slate-200
                        bg-slate-50
                        p-5
                        text-center
                    "
                >

                    <p class="font-semibold text-slate-700">
                        No existen unidades disponibles
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Las unidades deben completar su revisión y quedar en estado Lista para envío.
                    </p>

                </div>

            @endforelse

        </x-ui.card>

    @endif



    {{-- ============================================================
        UNIDADES INCLUIDAS
    ============================================================ --}}
    <x-ui.card padding="false">

        <div
            class="
                border-b
                border-slate-200
                px-5
                py-4
            "
        >

            <h2 class="text-lg font-bold text-slate-900">
                Unidades del envío
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Seguimiento individual de cada equipo incluido.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr
                        class="
                            border-b
                            border-slate-200
                            text-xs
                            font-semibold
                            uppercase
                            tracking-wide
                            text-slate-500
                        "
                    >

                        <th class="px-5 py-4 text-left">
                            Unidad
                        </th>

                        <th class="px-5 py-4 text-left">
                            Equipo
                        </th>

                        <th class="px-5 py-4 text-left">
                            Estado unidad
                        </th>

                        <th class="px-5 py-4 text-left">
                            Recepción
                        </th>

                        <th class="px-5 py-4 text-left">
                            Observación
                        </th>

                        <th class="px-5 py-4 text-right">
                            Acciones
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($envio->unidadesEnvio as $detalle)

                        @php
                            $unidad = $detalle->unidadAdquirida;

                            $colorRecepcion =
                                match ($detalle->estado_recepcion) {
                                    \App\Models\EnvioImportacionUnidad::ESTADO_RECIBIDA => 'green',
                                    \App\Models\EnvioImportacionUnidad::ESTADO_FALTANTE => 'red',
                                    \App\Models\EnvioImportacionUnidad::ESTADO_INCIDENCIA => 'yellow',
                                    default => 'gray',
                                };
                        @endphp


                        <tr class="hover:bg-slate-50">

                            {{-- Unidad --}}
                            <td class="px-5 py-4">

                                <a
                                    href="{{
                                        route(
                                            'unidades-adquiridas.show',
                                            $unidad
                                        )
                                    }}"
                                    class="
                                        font-semibold
                                        text-oneshop-primary
                                        hover:underline
                                    "
                                >
                                    {{
                                        $unidad?->codigo_trazabilidad
                                        ?? 'Sin código'
                                    }}
                                </a>

                            </td>


                            {{-- Equipo --}}
                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-900">
                                    {{
                                        $unidad?->producto?->nombre
                                        ??
                                        $unidad?->nombre_equipo
                                        ??
                                        'Equipo'
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    {{
                                        $unidad?->producto?->modelo
                                        ??
                                        $unidad?->modelo_equipo
                                        ??
                                        '-'
                                    }}
                                </p>

                            </td>


                            {{-- Estado unidad --}}
                            <td class="px-5 py-4">

                                <span
                                    class="
                                        text-xs
                                        font-semibold
                                        text-slate-700
                                    "
                                >
                                    {{
                                        str_replace(
                                            '_',
                                            ' ',
                                            $unidad?->estado
                                            ?? '-'
                                        )
                                    }}
                                </span>

                            </td>


                            {{-- Recepción --}}
                            <td class="px-5 py-4">

                                <x-ui.badge
                                    :color="$colorRecepcion"
                                >
                                    {{
                                        str_replace(
                                            '_',
                                            ' ',
                                            $detalle->estado_recepcion
                                        )
                                    }}
                                </x-ui.badge>


                                @if($detalle->fecha_recepcion)

                                    <p class="mt-1 text-xs text-slate-500">
                                        {{
                                            $detalle
                                                ->fecha_recepcion
                                                ->format('d/m/Y H:i')
                                        }}
                                    </p>

                                @endif

                            </td>


                            {{-- Observación --}}
                            <td class="px-5 py-4">

                                <p
                                    class="
                                        max-w-xs
                                        whitespace-pre-line
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    {{
                                        $detalle->observacion_recepcion
                                        ?? '-'
                                    }}
                                </p>

                            </td>


                            {{-- Acciones --}}
                            <td class="px-5 py-4 text-right">

                                <div
                                    class="
                                        flex
                                        flex-wrap
                                        justify-end
                                        gap-2
                                    "
                                >


                                    {{-- Quitar de borrador --}}
                                    @if(
                                        $envio->estaEnBorrador()
                                        &&
                                        auth()->user()?->tienePermiso('importacion.gestionar')
                                    )

                                        <button
                                            type="button"
                                            @click="
                                                ejecutar(
                                                    @js(
                                                        route(
                                                            'envios-importacion.unidades.quitar',
                                                            [
                                                                'envio' => $envio,
                                                                'unidad' => $unidad,
                                                            ]
                                                        )
                                                    ),
                                                    'delete'
                                                )
                                            "
                                            :disabled="procesando"
                                            class="
                                                inline-flex
                                                items-center
                                                gap-1
                                                rounded-lg
                                                border
                                                border-red-200
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-red-600
                                                hover:bg-red-50
                                            "
                                        >
                                            <x-ui.icon
                                                name="trash"
                                                size="15"
                                            />

                                            Quitar
                                        </button>

                                    @endif



                                    {{-- Recepción pendiente --}}
                                    @if(
                                        in_array(
                                            $envio->estado,
                                            [
                                                \App\Models\EnvioImportacion::ESTADO_DESPACHADO,
                                                \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                                            ],
                                            true
                                        )
                                        &&
                                        $detalle->estaPendiente()
                                        &&
                                        auth()->user()?->tienePermiso('importacion.gestionar')
                                    )

                                        <button
                                            type="button"
                                            @click="
                                                abrirRecepcion(
                                                    'recibir',
                                                    @js(
                                                        route(
                                                            'envios-importacion.unidades.recibir',
                                                            [
                                                                'envio' => $envio,
                                                                'unidad' => $unidad,
                                                            ]
                                                        )
                                                    ),
                                                    @js(
                                                        $unidad->codigo_trazabilidad
                                                        ?? 'Unidad'
                                                    )
                                                )
                                            "
                                            class="
                                                rounded-lg
                                                bg-green-600
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-white
                                                hover:bg-green-700
                                            "
                                        >
                                            Recibir
                                        </button>


                                        <button
                                            type="button"
                                            @click="
                                                abrirRecepcion(
                                                    'faltante',
                                                    @js(
                                                        route(
                                                            'envios-importacion.unidades.faltante',
                                                            [
                                                                'envio' => $envio,
                                                                'unidad' => $unidad,
                                                            ]
                                                        )
                                                    ),
                                                    @js(
                                                        $unidad->codigo_trazabilidad
                                                        ?? 'Unidad'
                                                    )
                                                )
                                            "
                                            class="
                                                rounded-lg
                                                border
                                                border-red-200
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-red-600
                                                hover:bg-red-50
                                            "
                                        >
                                            Faltante
                                        </button>


                                        <button
                                            type="button"
                                            @click="
                                                abrirRecepcion(
                                                    'incidencia',
                                                    @js(
                                                        route(
                                                            'envios-importacion.unidades.incidencia',
                                                            [
                                                                'envio' => $envio,
                                                                'unidad' => $unidad,
                                                            ]
                                                        )
                                                    ),
                                                    @js(
                                                        $unidad->codigo_trazabilidad
                                                        ?? 'Unidad'
                                                    )
                                                )
                                            "
                                            class="
                                                rounded-lg
                                                border
                                                border-yellow-300
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-yellow-700
                                                hover:bg-yellow-50
                                            "
                                        >
                                            Incidencia
                                        </button>

                                    @endif



                                    {{-- Recepción tardía --}}
                                    @if(
                                        $detalle->estaFaltante()
                                        &&
                                        $envio->estado ===
                                            \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL
                                        &&
                                        auth()->user()?->tienePermiso('importacion.gestionar')
                                    )

                                        <button
                                            type="button"
                                            @click="
                                                abrirRecepcion(
                                                    'recibir',
                                                    @js(
                                                        route(
                                                            'envios-importacion.unidades.recibir',
                                                            [
                                                                'envio' => $envio,
                                                                'unidad' => $unidad,
                                                            ]
                                                        )
                                                    ),
                                                    @js(
                                                        $unidad->codigo_trazabilidad
                                                        ?? 'Unidad'
                                                    )
                                                )
                                            "
                                            class="
                                                rounded-lg
                                                bg-green-600
                                                px-3
                                                py-2
                                                text-xs
                                                font-semibold
                                                text-white
                                                hover:bg-green-700
                                            "
                                        >
                                            Recibir ahora
                                        </button>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-5 py-14 text-center"
                            >

                                <x-ui.icon
                                    name="package"
                                    size="28"
                                    class="mx-auto text-slate-300"
                                />

                                <p class="mt-3 font-semibold text-slate-700">
                                    El envío todavía no tiene unidades
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </x-ui.card>



    {{-- ============================================================
        HISTORIAL GENERAL
    ============================================================ --}}
    <x-ui.card>

        <h2 class="text-lg font-bold text-slate-900">
            Historial del envío
        </h2>


        <div class="mt-5 space-y-5">

            <div class="flex gap-4">

                <div
                    class="
                        flex
                        h-9
                        w-9
                        shrink-0
                        items-center
                        justify-center
                        rounded-full
                        bg-green-100
                        text-green-700
                    "
                >
                    <x-ui.icon
                        name="check"
                        size="16"
                    />
                </div>

                <div>

                    <p class="font-semibold text-slate-900">
                        Borrador creado
                    </p>

                    <p class="text-xs text-slate-500">
                        {{
                            $envio->created_at?->format('d/m/Y H:i')
                            ?? '-'
                        }}
                    </p>

                </div>

            </div>


            @if($envio->fecha_preparacion)

                <div class="flex gap-4">

                    <div
                        class="
                            flex
                            h-9
                            w-9
                            shrink-0
                            items-center
                            justify-center
                            rounded-full
                            bg-blue-100
                            text-blue-700
                        "
                    >
                        <x-ui.icon
                            name="package"
                            size="16"
                        />
                    </div>

                    <div>

                        <p class="font-semibold text-slate-900">
                            Envío preparado
                        </p>

                        <p class="text-xs text-slate-500">
                            {{
                                $envio
                                    ->fecha_preparacion
                                    ->format('d/m/Y H:i')
                            }}

                            @if($envio->preparadoPor)

                                ·
                                {{ $envio->preparadoPor->name }}

                            @endif
                        </p>

                    </div>

                </div>

            @endif


            @if($envio->fecha_despacho)

                <div class="flex gap-4">

                    <div
                        class="
                            flex
                            h-9
                            w-9
                            shrink-0
                            items-center
                            justify-center
                            rounded-full
                            bg-yellow-100
                            text-yellow-700
                        "
                    >
                        <x-ui.icon
                            name="truck"
                            size="16"
                        />
                    </div>

                    <div>

                        <p class="font-semibold text-slate-900">
                            Despachado a Oruro
                        </p>

                        <p class="text-xs text-slate-500">
                            {{
                                $envio
                                    ->fecha_despacho
                                    ->format('d/m/Y H:i')
                            }}

                            @if($envio->despachadoPor)

                                ·
                                {{ $envio->despachadoPor->name }}

                            @endif
                        </p>

                    </div>

                </div>

            @endif


            @if($envio->fecha_recepcion)

                <div class="flex gap-4">

                    <div
                        class="
                            flex
                            h-9
                            w-9
                            shrink-0
                            items-center
                            justify-center
                            rounded-full
                            bg-green-100
                            text-green-700
                        "
                    >
                        <x-ui.icon
                            name="warehouse"
                            size="16"
                        />
                    </div>

                    <div>

                        <p class="font-semibold text-slate-900">
                            Recepción completada en Oruro
                        </p>

                        <p class="text-xs text-slate-500">
                            {{
                                $envio
                                    ->fecha_recepcion
                                    ->format('d/m/Y H:i')
                            }}

                            @if($envio->recibidoPor)

                                ·
                                {{ $envio->recibidoPor->name }}

                            @endif
                        </p>

                    </div>

                </div>

            @endif

        </div>

    </x-ui.card>



    {{-- ============================================================
        MODAL DESPACHO
    ============================================================ --}}
    <div
        x-cloak
        x-show="modalDespacho"
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

        <div
            class="
                absolute
                inset-0
                bg-slate-950/70
                backdrop-blur-sm
            "
            @click="!procesando && (modalDespacho = false)"
        ></div>


        <div
            class="
                relative
                z-10
                w-full
                max-w-xl
                rounded-2xl
                bg-white
                p-6
                shadow-2xl
            "
        >

            <h3 class="text-lg font-bold text-slate-900">
                Despachar envío a Oruro
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Confirme los datos del traslado antes de realizar el despacho.
            </p>


            <div class="mt-6 space-y-4">

                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Transportista
                    </label>

                    <input
                        type="text"
                        x-model="despacho.transportista"
                        class="input-oneshop w-full"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-sm font-semibold text-slate-700">
                        Número de guía
                    </label>

                    <input
                        type="text"
                        x-model="despacho.numero_guia"
                        class="input-oneshop w-full"
                    >

                </div>

            </div>


            <div
                class="
                    mt-6
                    flex
                    justify-end
                    gap-3
                    border-t
                    border-slate-200
                    pt-5
                "
            >

                <button
                    type="button"
                    @click="modalDespacho = false"
                    class="btn-secondary"
                >
                    Cancelar
                </button>


                <button
                    type="button"
                    @click="despachar()"
                    :disabled="procesando"
                    class="
                        inline-flex
                        items-center
                        gap-2
                        rounded-xl
                        bg-oneshop-primary
                        px-5
                        py-2.5
                        text-sm
                        font-semibold
                        text-white
                        hover:bg-oneshop-dark
                        disabled:opacity-50
                    "
                >
                    <x-ui.icon
                        name="truck"
                        size="17"
                    />

                    Confirmar despacho
                </button>

            </div>

        </div>

    </div>



    {{-- ============================================================
        MODAL RECEPCIÓN
    ============================================================ --}}
    <div
        x-cloak
        x-show="modalRecepcion"
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

        <div
            class="
                absolute
                inset-0
                bg-slate-950/70
                backdrop-blur-sm
            "
            @click="!procesando && (modalRecepcion = false)"
        ></div>


        <div
            class="
                relative
                z-10
                w-full
                max-w-xl
                rounded-2xl
                bg-white
                p-6
                shadow-2xl
            "
        >

            <h3
                class="text-lg font-bold text-slate-900"
                x-text="
                    tipoRecepcion === 'recibir'
                        ? 'Registrar recepción'
                        : (
                            tipoRecepcion === 'faltante'
                                ? 'Registrar unidad faltante'
                                : 'Registrar incidencia'
                        )
                "
            ></h3>


            <p class="mt-1 text-sm text-slate-500">
                Unidad:
                <strong
                    class="text-slate-700"
                    x-text="unidadRecepcion"
                ></strong>
            </p>


            <div class="mt-6">

                <label class="mb-2 block text-sm font-semibold text-slate-700">

                    <span
                        x-text="
                            tipoRecepcion === 'recibir'
                                ? 'Observación'
                                : 'Detalle de la situación'
                        "
                    ></span>

                </label>


                <textarea
                    x-model="observacionRecepcion"
                    rows="5"
                    class="input-oneshop w-full"
                    :placeholder="
                        tipoRecepcion === 'recibir'
                            ? 'Observación opcional de recepción'
                            : 'Describa lo ocurrido'
                    "
                ></textarea>


                <p
                    x-show="
                        tipoRecepcion === 'faltante'
                        ||
                        tipoRecepcion === 'incidencia'
                    "
                    class="mt-1 text-xs text-slate-500"
                >
                    La observación es obligatoria para esta operación.
                </p>

            </div>


            <div
                class="
                    mt-6
                    flex
                    justify-end
                    gap-3
                    border-t
                    border-slate-200
                    pt-5
                "
            >

                <button
                    type="button"
                    @click="modalRecepcion = false"
                    class="btn-secondary"
                >
                    Cancelar
                </button>


                <button
                    type="button"
                    @click="guardarRecepcion()"
                    :disabled="procesando"
                    class="
                        rounded-xl
                        bg-oneshop-primary
                        px-5
                        py-2.5
                        text-sm
                        font-semibold
                        text-white
                        hover:bg-oneshop-dark
                        disabled:opacity-50
                    "
                >
                    Confirmar
                </button>

            </div>

        </div>

    </div>


</div>

</x-layouts.oneshop>