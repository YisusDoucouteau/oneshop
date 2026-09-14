<div
    x-data="{
        modalRevision: false,
        guardandoRevision: false,
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

            enciende: @js((bool) $unidad->enciende),
            tiene_sistema_operativo: @js((bool) $unidad->tiene_sistema_operativo),
            tiene_cargador: @js((bool) $unidad->tiene_cargador),
            requiere_servicio: @js((bool) $unidad->requiere_servicio),

            servicio_requerido: @js($unidad->servicio_requerido),
            observacion_revision: @js($unidad->observacion_revision)
        },

        async guardarRevision() {

            if (this.guardandoRevision) {
                return;
            }

            this.guardandoRevision = true;
            this.erroresRevision = {};
            this.errorRevision = '';

            try {

                const respuesta =
                    await window.axios.patch(
                        @js(route(
                            'unidades-adquiridas.revision',
                            $unidad
                        )),
                        this.revision,
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

                this.errorRevision =
                    'No fue posible guardar la revisión.';

            } catch (error) {

                if (error.response?.status === 422) {

                    this.erroresRevision =
                        error.response.data.errors ?? {};

                    this.errorRevision =
                        error.response.data.message
                        ??
                        'Revise los datos ingresados.';

                } else {

                    this.errorRevision =
                        error.response?.data?.message
                        ??
                        'Ocurrió un error al guardar la revisión.';

                }

            } finally {

                this.guardandoRevision = false;

            }
        }
    }"
    class="bg-white rounded-2xl shadow-oneshop p-6"
>


   <div class="flex flex-wrap items-center gap-3 mb-5">

        <x-ui.icon
            name="wrench"
            class="text-oneshop-primary"
        />

        <h3 class="text-lg font-bold text-gray-800">
            Preparación y revisión técnica
        </h3>
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

    <div class="ml-auto">

        <button
            type="button"
            @click="modalRevision = true"
            class="
                inline-flex
                items-center
                gap-2
                rounded-xl
                border
                border-slate-200
                bg-white
                px-4
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
            <x-ui.icon name="edit" size="17"/>

            Revisar unidad
        </button>

    </div>

@endif
    </div>



    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">


        <div>

            <p class="text-sm text-gray-500">
                Estado de preparación
            </p>

            <p class="font-semibold text-gray-800">

                {{ 
                    $unidad->estado 
                    ??
                    'Sin estado'
                }}

            </p>

        </div>



        <div>

            <p class="text-sm text-gray-500">
                Requiere servicio
            </p>


            <p class="font-semibold text-gray-800">

                @if($unidad->requiere_servicio)

                    Sí

                @else

                    No

                @endif

            </p>

        </div>



        <div>

            <p class="text-sm text-gray-500">
                Servicio requerido
            </p>


            <p class="font-semibold text-gray-800">

                {{
                    $unidad->servicio_requerido
                    ??
                    'Sin servicios registrados'
                }}

            </p>


        </div>



        <div>

            <p class="text-sm text-gray-500">
                Cargador
            </p>


            <p class="font-semibold text-gray-800">

                @if(is_null($unidad->tiene_cargador))

                    No evaluado

                @elseif($unidad->tiene_cargador)

                    Incluido

                @else

                    Faltante

                @endif

            </p>


        </div>



    </div>



    <div class="mt-6">


        <p class="text-sm text-gray-500 mb-2">
            Observaciones de revisión
        </p>


        <div class="bg-gray-50 rounded-xl p-4 text-gray-700">


            {{
                $unidad->observacion_revision
                ??
                'Sin observaciones registradas.'
            }}


        </div>


    </div>



    @if($unidad->intervenciones && $unidad->intervenciones->count())


        <div class="mt-6">

            <h4 class="font-semibold text-gray-800 mb-3">
                Intervenciones realizadas
            </h4>


            <div class="space-y-3">


                @foreach($unidad->intervenciones as $intervencion)


                    <div class="border rounded-xl p-4">


                        <div class="flex justify-between">


                            <span class="font-medium">

                                {{
                                    $intervencion->tipo
                                    ??
                                    'Servicio'
                                }}

                            </span>


                            <span class="text-sm text-gray-500">

                                {{
                                    optional(
                                        $intervencion->fecha_inicio
                                    )->format('d/m/Y')
                                    ??
                                    ''
                                }}

                            </span>


                        </div>


                        <p class="text-sm text-gray-600 mt-2">

                            {{
                                $intervencion->descripcion
                                ??
                                ''
                            }}

                        </p>


                    </div>


                @endforeach


            </div>


        </div>


    @endif

@include(
    'unidades_adquiridas.partials.modal-revision',
    [
        'unidad' => $unidad
    ]
)

</div>