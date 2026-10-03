@php
    $detalleEnvio = $unidad->envioImportacionUnidad;
    $envio = $detalleEnvio?->envioImportacion;

    $recepcionLogisticaCerrada =
        !$unidad->provieneDeLote()
        || (
            $envio
            && in_array(
                $envio->estado,
                [
                    \App\Models\EnvioImportacion::ESTADO_RECIBIDO,
                    \App\Models\EnvioImportacion::ESTADO_RECIBIDO_PARCIAL,
                ],
                true
            )
            && in_array(
                $detalleEnvio?->estado_recepcion,
                [
                    \App\Models\EnvioImportacionUnidad::ESTADO_RECIBIDA,
                    \App\Models\EnvioImportacionUnidad::ESTADO_INCIDENCIA,
                ],
                true
            )
        );

    $usuarioPuedeIncorporar =
        auth()->user()?->tienePermiso('inventario.registrar')
        && $unidad->almacen_actual_id
        && auth()->user()?->puedeOperarEnAlmacen(
            (int) $unidad->almacen_actual_id
        );
@endphp

<div
    x-data="{
        modalIncorporacion: false,
        guardando: false,
        errores: {},
        errorGeneral: '',

        formulario: {
            condicion_fisica_id: '',
            serial_fabricante: @js($unidad->serial_fabricante ?? ''),
            observacion: ''
        },

        async incorporarUnidad() {
            if (this.guardando) {
                return;
            }

            this.guardando = true;
            this.errores = {};
            this.errorGeneral = '';

            try {
                const respuesta = await window.axios.post(
                    @js(route('unidades-adquiridas.incorporar', $unidad)),
                    this.formulario,
                    {
                        headers: {
                            'Accept': 'application/json'
                        }
                    }
                );

                if (respuesta.data?.ok) {
                    window.location.href =
                        respuesta.data.redirect
                        ?? @js(route('unidades-adquiridas.show', $unidad));

                    return;
                }

                this.errorGeneral =
                    'No fue posible completar la incorporación.';

            } catch (error) {
                if (error.response?.status === 422) {
                    this.errores =
                        error.response.data.errors ?? {};

                    this.errorGeneral =
                        error.response.data.message
                        ?? 'Revise los datos ingresados.';
                } else {
                    this.errorGeneral =
                        error.response?.data?.message
                        ?? 'Ocurrió un error al incorporar la unidad.';
                }
            } finally {
                this.guardando = false;
            }
        }
    }"
    class="rounded-2xl border border-slate-200 bg-white p-6 shadow-oneshop"
>

    <div class="mb-5 flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-oneshop-light text-oneshop-primary">
            <x-ui.icon name="package" size="20" />
        </div>

        <div>
            <h3 class="text-lg font-bold text-slate-900">
                Incorporación a inventario
            </h3>

            <p class="mt-0.5 text-xs text-slate-500">
                Formaliza la unidad como equipo de inventario en Oruro.
            </p>
        </div>
    </div>


    @if($unidad->incorporacionInventario)

        <div class="space-y-5">
            <div class="rounded-xl border border-green-200 bg-green-50 p-4">
                <p class="font-semibold text-green-800">
                    Unidad incorporada
                </p>
                <p class="mt-1 text-sm text-green-700">
                    La unidad ya forma parte del inventario formal de OneShop.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <div>
                    <p class="text-sm text-slate-500">Fecha de incorporación</p>
                    <p class="font-semibold text-slate-800">
                        {{
                            optional(
                                $unidad
                                    ->incorporacionInventario
                                    ->fecha_incorporacion
                            )->format('d/m/Y H:i')
                            ?? 'Sin fecha'
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Responsable</p>
                    <p class="font-semibold text-slate-800">
                        {{
                            $unidad
                                ->incorporacionInventario
                                ->usuario
                                ?->name
                            ?? 'Sin usuario'
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Condición física</p>
                    <p class="font-semibold text-slate-800">
                        {{
                            $unidad
                                ->incorporacionInventario
                                ->condicionFisica
                                ?->nombre
                            ?? 'Sin condición'
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-sm text-slate-500">Código de inventario</p>
                    <p class="font-semibold text-oneshop-primary">
                        {{ $unidad->equipo?->codigo_interno ?? 'Sin código' }}
                    </p>
                </div>
            </div>

            @if($unidad->equipo)
                <a
                    href="{{ route('inventario.show', $unidad->equipo->codigo_interno) }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-4 py-2.5 text-sm font-semibold text-oneshop-dark transition hover:bg-blue-100"
                >
                    <x-ui.icon name="eye" size="17" />
                    Ver ficha de inventario
                </a>
            @endif
        </div>


    @elseif(
        $unidad->estado ===
        \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORURO
        && !$recepcionLogisticaCerrada
    )

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="font-semibold text-amber-900">
                Esperando cierre de recepción
            </p>
            <p class="mt-1 text-sm text-amber-800">
                La unidad ya llegó a Oruro, pero el envío todavía debe cerrarse en destino antes de formalizarla en inventario.
            </p>
        </div>


    @elseif(
        $unidad->estado ===
        \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORURO
        && $recepcionLogisticaCerrada
    )

        <div class="space-y-4">
            <div class="rounded-xl border border-blue-200 bg-oneshop-soft p-4">
                <p class="font-semibold text-slate-900">
                    Unidad lista para incorporación
                </p>
                <p class="mt-1 text-sm text-slate-600">
                    La recepción física está cerrada y la unidad puede convertirse en equipo formal de inventario.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Ubicación</p>
                    <p class="mt-1 font-semibold text-slate-800">Oruro principal</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Código</p>
                    <p class="mt-1 font-semibold text-slate-800">Automático</p>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Estado inicial</p>
                    <p class="mt-1 font-semibold text-slate-800">Recibido</p>
                </div>
            </div>

            @if($usuarioPuedeIncorporar)
                <button
                    type="button"
                    @click="modalIncorporacion = true"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-oneshop-primary bg-oneshop-light px-5 py-3 text-sm font-semibold text-oneshop-dark transition hover:bg-blue-100"
                >
                    <x-ui.icon name="package" size="18" />
                    Incorporar al inventario
                </button>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                    La incorporación debe realizarla un usuario autorizado para operar en el almacén de Oruro.
                </div>
            @endif
        </div>


    @elseif(
        $unidad->estado ===
        \App\Models\UnidadAdquirida::ESTADO_ANULADA
    )

        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            La unidad fue anulada y no puede incorporarse al inventario.
        </div>


    @else

        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="font-semibold text-slate-800">
                Incorporación todavía no disponible
            </p>
            <p class="mt-1 text-sm text-slate-500">
                La unidad debe completar preparación, despacho, recepción física y cierre logístico antes de ingresar al inventario formal.
            </p>
        </div>

    @endif


    @include(
        'unidades_adquiridas.partials.modal-incorporar',
        [
            'unidad' => $unidad,
            'condicionesFisicas' => $condicionesFisicas,
        ]
    )

</div>
