<x-layouts.oneshop
    title="Envíos a Oruro | OneShop"
    page-title="Envíos a Oruro"
>

@php
    $colorEstado = function (?string $estado): string {
        return match ($estado) {
            \App\Models\EnvioImportacion::ESTADO_BORRADOR => 'gray',
            \App\Models\EnvioImportacion::ESTADO_PREPARADO => 'blue',
            \App\Models\EnvioImportacion::ESTADO_DESPACHADO => 'yellow',
            \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL => 'yellow',
            \App\Models\EnvioImportacion::ESTADO_RECIBIDO => 'green',
            \App\Models\EnvioImportacion::ESTADO_CANCELADO => 'red',
            default => 'gray',
        };
    };

    $nombreEstado = function (?string $estado): string {
        return match ($estado) {
            'BORRADOR' => 'Borrador',
            'PREPARADO' => 'Preparado',
            'DESPACHADO' => 'Despachado',
            'RECIBIDO_PARCIAL' => 'Recepción parcial',
            'RECIBIDO' => 'Recibido',
            'CANCELADO' => 'Cancelado',
            default => str_replace('_', ' ', $estado ?? 'Sin estado'),
        };
    };
@endphp


<div
    x-data="{
        modalCrear: false,
        guardando: false,
        errores: {},
        errorGeneral: '',

        formulario: {
            transportista: '',
            numero_guia: '',
            cantidad_bultos: 1,
            cantidad_cargadores: 0,
            cantidad_accesorios: 0,
            detalle_accesorios: '',
            observacion: ''
        },

        abrirModal() {
            this.errores = {};
            this.errorGeneral = '';
            this.modalCrear = true;
        },

        async crearEnvio() {

            if (this.guardando) {
                return;
            }

            this.guardando = true;
            this.errores = {};
            this.errorGeneral = '';

            try {

                const respuesta =
                    await window.axios.post(
                        @js(route('envios-importacion.store')),
                        this.formulario,
                        {
                            headers: {
                                'Accept': 'application/json'
                            }
                        }
                    );

                if (
                    respuesta.data?.ok
                    &&
                    respuesta.data?.redirect
                ) {
                    window.location.href =
                        respuesta.data.redirect;

                    return;
                }

                this.errorGeneral =
                    'No fue posible crear el envío.';

            } catch (error) {

                if (
                    error.response?.status === 422
                ) {

                    this.errores =
                        error.response.data.errors
                        ?? {};

                    this.errorGeneral =
                        error.response.data.message
                        ?? 'Revise los datos ingresados.';

                } else {

                    this.errorGeneral =
                        error.response?.data?.message
                        ?? 'Ocurrió un error al crear el envío.';
                }

            } finally {
                this.guardando = false;
            }
        }
    }"
    class="space-y-6"
>


    {{-- ============================================================
        ENCABEZADO
    ============================================================ --}}
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

        <div class="flex items-center gap-3">

            <div
                class="
                    flex
                    h-11
                    w-11
                    items-center
                    justify-center
                    rounded-xl
                    bg-oneshop-light
                    text-oneshop-primary
                "
            >
                <x-ui.icon
                    name="truck"
                    size="22"
                />
            </div>


            <div>

                <h1 class="text-2xl font-bold text-slate-900">
                    Envíos a Oruro
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Gestión del traslado de unidades desde Cochabamba hasta Oruro.
                </p>

            </div>

        </div>


        @if(auth()->user()?->tienePermiso('importacion.gestionar') && ($puedeCrearEnvio ?? false))

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
                    px-5
                    py-3
                    text-sm
                    font-semibold
                    text-white
                    transition
                    hover:bg-oneshop-dark
                "
            >
                <x-ui.icon
                    name="plus"
                    size="18"
                />

                Nuevo envío
            </button>

        @endif

    </div>


    {{-- ============================================================
        MENSAJES
    ============================================================ --}}
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
        RESUMEN
    ============================================================ --}}
    <div
        class="
            grid
            grid-cols-1
            gap-4
            sm:grid-cols-2
            xl:grid-cols-4
        "
    >

        <x-ui.card>

            <p class="text-sm text-slate-500">
                Total de envíos
            </p>

            <p class="mt-2 text-2xl font-bold text-slate-900">
                {{ $envios->total() }}
            </p>

        </x-ui.card>


        <x-ui.card>

            <p class="text-sm text-slate-500">
                Unidades listas para envío
            </p>

            <p class="mt-2 text-2xl font-bold text-slate-900">
                {{ $unidadesDisponibles->count() }}
            </p>

        </x-ui.card>


        <x-ui.card>

            <p class="text-sm text-slate-500">
                Ruta
            </p>

            <div class="mt-2 flex items-center gap-2 font-semibold text-slate-900">

                <span>Cochabamba</span>

                <x-ui.icon
                    name="truck"
                    size="16"
                    class="text-oneshop-primary"
                />

                <span>Oruro</span>

            </div>

        </x-ui.card>


        <x-ui.card>

            <p class="text-sm text-slate-500">
                Etapa
            </p>

            <p class="mt-2 font-semibold text-slate-900">
                Preinventario
            </p>

        </x-ui.card>

    </div>


    {{-- ============================================================
        LISTADO
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

            <h2 class="font-semibold text-slate-900">
                Registro de envíos
            </h2>

            <p class="mt-1 text-xs text-slate-500">
                Seguimiento de preparación, despacho y recepción en Oruro.
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
                            Envío
                        </th>

                        <th class="px-5 py-4 text-left">
                            Ruta
                        </th>

                        <th class="px-5 py-4 text-left">
                            Estado
                        </th>

                        <th class="px-5 py-4 text-left">
                            Unidades
                        </th>

                        <th class="px-5 py-4 text-left">
                            Transporte
                        </th>

                        <th class="px-5 py-4 text-left">
                            Fecha
                        </th>

                        <th class="px-5 py-4 text-right">
                            Acción
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($envios as $envio)

                        <tr class="transition hover:bg-slate-50">

                            {{-- Envío --}}
                            <td class="px-5 py-4">

                                <div class="flex items-center gap-3">

                                    <div
                                        class="
                                            flex
                                            h-10
                                            w-10
                                            shrink-0
                                            items-center
                                            justify-center
                                            rounded-xl
                                            bg-oneshop-soft
                                            text-oneshop-primary
                                        "
                                    >
                                        <x-ui.icon
                                            name="truck"
                                            size="19"
                                        />
                                    </div>


                                    <div>

                                        <p class="font-semibold text-slate-900">
                                            {{ $envio->codigo }}
                                        </p>

                                        <p class="mt-1 text-xs text-slate-500">
                                            ID {{ $envio->id }}
                                        </p>

                                    </div>

                                </div>

                            </td>


                            {{-- Ruta --}}
                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-800">
                                    {{
                                        $envio->almacenOrigen?->nombre
                                        ?? 'Cochabamba'
                                    }}
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    hacia
                                    {{
                                        $envio->almacenDestino?->nombre
                                        ?? 'Oruro'
                                    }}
                                </p>

                            </td>


                            {{-- Estado --}}
                            <td class="px-5 py-4">

                                <x-ui.badge
                                    :color="$colorEstado($envio->estado)"
                                >
                                    {{ $nombreEstado($envio->estado) }}
                                </x-ui.badge>

                            </td>


                            {{-- Unidades --}}
                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-900">
                                    {{ $envio->unidades_envio_count }}
                                </p>

                                <p class="text-xs text-slate-500">
                                    unidades
                                </p>

                            </td>


                            {{-- Transporte --}}
                            <td class="px-5 py-4">

                                <p class="text-slate-700">
                                    {{ $envio->transportista ?? '-' }}
                                </p>

                                @if($envio->numero_guia)

                                    <p class="mt-1 text-xs text-slate-500">
                                        Guía:
                                        {{ $envio->numero_guia }}
                                    </p>

                                @endif

                            </td>


                            {{-- Fecha --}}
                            <td class="px-5 py-4">

                                @if($envio->fecha_despacho)

                                    <p class="font-medium text-slate-700">
                                        {{
                                            $envio
                                                ->fecha_despacho
                                                ->format('d/m/Y')
                                        }}
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        Despacho
                                    </p>

                                @elseif($envio->fecha_preparacion)

                                    <p class="font-medium text-slate-700">
                                        {{
                                            $envio
                                                ->fecha_preparacion
                                                ->format('d/m/Y')
                                        }}
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        Preparación
                                    </p>

                                @else

                                    <p class="font-medium text-slate-700">
                                        {{
                                            $envio
                                                ->created_at
                                                ?->format('d/m/Y')
                                            ?? '-'
                                        }}
                                    </p>

                                    <p class="text-xs text-slate-500">
                                        Creación
                                    </p>

                                @endif

                            </td>


                            {{-- Acción --}}
                            <td class="px-5 py-4 text-right">

                                <a
                                    href="{{
                                        route(
                                            'envios-importacion.show',
                                            $envio
                                        )
                                    }}"
                                    class="
                                        inline-flex
                                        items-center
                                        gap-2
                                        rounded-xl
                                        border
                                        border-slate-200
                                        bg-white
                                        px-3.5
                                        py-2
                                        text-sm
                                        font-semibold
                                        text-slate-700
                                        transition
                                        hover:border-oneshop-primary
                                        hover:bg-oneshop-soft
                                        hover:text-oneshop-primary
                                    "
                                >
                                    <x-ui.icon
                                        name="eye"
                                        size="17"
                                    />

                                    Ver envío
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-6 py-16 text-center"
                            >

                                <div
                                    class="
                                        mx-auto
                                        flex
                                        h-14
                                        w-14
                                        items-center
                                        justify-center
                                        rounded-2xl
                                        bg-slate-100
                                        text-slate-400
                                    "
                                >
                                    <x-ui.icon
                                        name="truck"
                                        size="25"
                                    />
                                </div>

                                <h3 class="mt-4 font-semibold text-slate-900">
                                    No existen envíos registrados
                                </h3>

                                <p class="mt-1 text-sm text-slate-500">
                                    Cree un borrador para comenzar un nuevo traslado a Oruro.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($envios->hasPages())

            <div
                class="
                    border-t
                    border-slate-200
                    px-5
                    py-4
                "
            >
                {{ $envios->links() }}
            </div>

        @endif

    </x-ui.card>



    {{-- ============================================================
        MODAL CREAR ENVÍO
    ============================================================ --}}
    <div
        x-cloak
        x-show="modalCrear"
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

        <div
            class="
                absolute
                inset-0
                bg-slate-950/70
                backdrop-blur-sm
            "
            @click="!guardando && (modalCrear = false)"
        ></div>


        <div
            x-show="modalCrear"
            x-transition
            @click.stop
            class="
                relative
                z-10
                w-full
                max-w-2xl
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
                        <x-ui.icon
                            name="truck"
                            size="20"
                        />
                    </div>

                    <div>

                        <h3 class="text-lg font-bold text-slate-900">
                            Nuevo envío a Oruro
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Primero se creará como borrador. Luego podrá agregar las unidades.
                        </p>

                    </div>

                </div>


                <button
                    type="button"
                    @click="modalCrear = false"
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


            {{-- Formulario --}}
            <form
                @submit.prevent="crearEnvio"
                class="p-6"
            >

                <div class="space-y-5">


                    {{-- Código automático --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700">
                            Código del envío
                        </p>
                        <p class="mt-1 text-sm text-slate-500">
                            Se generará automáticamente al crear el envío (por ejemplo, ENV-{{ now()->format('Y') }}-001).
                        </p>
                    </div>


                    <div
                        class="
                            grid
                            grid-cols-1
                            gap-5
                            sm:grid-cols-2
                        "
                    >

                        {{-- Transportista --}}
                        <div>

                            <label
                                class="
                                    mb-2
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Transportista
                            </label>

                            <input
                                type="text"
                                x-model="formulario.transportista"
                                class="input-oneshop w-full"
                                placeholder="Empresa o persona"
                            >

                        </div>


                        {{-- Guía --}}
                        <div>

                            <label
                                class="
                                    mb-2
                                    block
                                    text-sm
                                    font-semibold
                                    text-slate-700
                                "
                            >
                                Número de guía
                            </label>

                            <input
                                type="text"
                                x-model="formulario.numero_guia"
                                class="input-oneshop w-full"
                                placeholder="Opcional"
                            >

                        </div>

                    </div>


                    {{-- Cajas --}}
                    <div>

                        <label
                            class="
                                mb-2
                                block
                                text-sm
                                font-semibold
                                text-slate-700
                            "
                        >
                            Cantidad de cajas
                        </label>

                        <input
                            type="number"
                            min="1"
                            x-model.number="formulario.cantidad_bultos"
                            class="input-oneshop w-full"
                        >

                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Cargadores adicionales
                            </label>
                            <input
                                type="number"
                                min="0"
                                x-model.number="formulario.cantidad_cargadores"
                                class="input-oneshop w-full"
                                title="Cargadores sueltos que viajan aparte de los asignados a cada equipo"
                            >
                            <p class="mt-1 text-xs text-slate-500">
                                Solo cargadores sueltos para stock o venta. Los cargadores que viajan con cada equipo se controlan individualmente.
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-semibold text-slate-700">
                                Otros accesorios
                            </label>
                            <input
                                type="number"
                                min="0"
                                x-model.number="formulario.cantidad_accesorios"
                                class="input-oneshop w-full"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">
                            Detalle de accesorios
                        </label>
                        <input
                            type="text"
                            x-model="formulario.detalle_accesorios"
                            class="input-oneshop w-full"
                            placeholder="Ej.: 1 mouse, 2 cables de poder"
                        >
                    </div>


                    {{-- Observación --}}
                    <div>

                        <label
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
                            x-model="formulario.observacion"
                            rows="4"
                            class="input-oneshop w-full"
                            placeholder="Información adicional del traslado"
                        ></textarea>

                    </div>


                    {{-- Error --}}
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
                        border-t
                        border-slate-200
                        pt-5
                        sm:flex-row
                        sm:justify-end
                    "
                >

                    <button
                        type="button"
                        @click="modalCrear = false"
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
                                    ? 'Creando...'
                                    : 'Crear borrador'
                            "
                        ></span>

                    </button>

                </div>

            </form>

        </div>

    </div>


</div>

</x-layouts.oneshop>