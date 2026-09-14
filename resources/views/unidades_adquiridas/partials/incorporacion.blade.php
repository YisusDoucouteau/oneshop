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
                    @js(route(
                        'unidades-adquiridas.incorporar',
                        $unidad
                    )),
                    this.formulario,
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

                this.errorGeneral =
                    'No fue posible completar la incorporación.';

            } catch (error) {

                if (error.response?.status === 422) {

                    this.errores =
                        error.response.data.errors ?? {};

                    this.errorGeneral =
                        error.response.data.message
                        ??
                        'Revise los datos ingresados.';

                } else {

                    this.errorGeneral =
                        error.response?.data?.message
                        ??
                        'Ocurrió un error al incorporar la unidad.';

                }

            } finally {

                this.guardando = false;

            }

        }
    }"
    class="bg-white rounded-2xl shadow-oneshop p-6"
>

    <div class="flex items-center gap-3 mb-5">

        <x-ui.icon
            name="package"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Incorporación a inventario
        </h3>

    </div>


    @if($unidad->incorporacionInventario)

        <div class="space-y-5">

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <p class="text-sm text-gray-500">
                        Fecha de incorporación
                    </p>

                    <p class="font-semibold text-gray-800">
                        {{
                            optional(
                                $unidad
                                    ->incorporacionInventario
                                    ->fecha_incorporacion
                            )
                            ->format('d/m/Y H:i')
                            ??
                            'Sin fecha'
                        }}
                    </p>
                </div>


                <div>
                    <p class="text-sm text-gray-500">
                        Responsable
                    </p>

                    <p class="font-semibold text-gray-800">
                        {{
                            $unidad
                                ->incorporacionInventario
                                ->usuario
                                ?->name
                            ??
                            'Sin usuario'
                        }}
                    </p>
                </div>


                <div>
                    <p class="text-sm text-gray-500">
                        Condición física
                    </p>

                    <p class="font-semibold text-gray-800">
                        {{
                            $unidad
                                ->incorporacionInventario
                                ->condicionFisica
                                ?->nombre
                            ??
                            'Sin condición'
                        }}
                    </p>
                </div>


                <div>
                    <p class="text-sm text-gray-500">
                        Código de inventario
                    </p>

                    <p class="font-semibold text-oneshop-primary">
                        {{
                            $unidad->equipo?->codigo_interno
                            ??
                            'Sin código'
                        }}
                    </p>
                </div>

            </div>


            @if($unidad->equipo)

                <a
                    href="{{
                        route(
                            'inventario.show',
                            $unidad->equipo
                        )
                    }}"
                    class="
                        inline-flex
                        items-center
                        gap-2
                        rounded-xl
                        border
                        border-slate-200
                        px-4
                        py-2.5
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

                    Ver equipo en inventario
                </a>

            @endif

        </div>


    @elseif(
        $unidad->estado ===
        \App\Models\UnidadAdquirida::ESTADO_RECIBIDA_ORURO
    )

        <div
            class="
                rounded-xl
                border
                border-blue-100
                bg-oneshop-soft
                p-4
            "
        >

            <p class="font-semibold text-slate-900">
                Unidad lista para incorporación
            </p>

            <p class="mt-1 text-sm text-slate-600">
                La unidad ya fue recibida en Oruro y puede convertirse en equipo formal de inventario.
            </p>

        </div>


        @if(auth()->user()?->tienePermiso('inventario.registrar'))

            <button
                type="button"
                @click="modalIncorporacion = true"
                class="
                    mt-5
                    inline-flex
                    w-full
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
                    name="package"
                    size="18"
                />

                Incorporar al inventario
            </button>

        @endif


    @elseif(
        $unidad->estado ===
        \App\Models\UnidadAdquirida::ESTADO_ANULADA
    )

        <div
            class="
                rounded-xl
                border
                border-red-200
                bg-red-50
                p-4
                text-sm
                text-red-700
            "
        >
            La unidad fue anulada y no puede incorporarse al inventario.
        </div>


    @else

        <div
            class="
                rounded-xl
                border
                border-slate-200
                bg-slate-50
                p-4
            "
        >

            <p class="font-semibold text-slate-800">
                Incorporación todavía no disponible
            </p>

            <p class="mt-1 text-sm text-slate-500">
                La unidad debe completar su preparación, despacho y recepción en Oruro antes de ingresar al inventario.
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