@php
    $detalleEnvio =
        $unidad->envioImportacionUnidad;

    $envio =
        $detalleEnvio?->envioImportacion;

    $eventos = collect();


    /*
    |--------------------------------------------------------------------------
    | Registro inicial
    |--------------------------------------------------------------------------
    */

    if ($unidad->created_at) {

        $eventos->push([
            'fecha' => $unidad->created_at,
            'titulo' => 'Unidad registrada',
            'descripcion' =>
                'La unidad fue registrada en el flujo de adquisición.',
            'ubicacion' => null,
            'icono' => 'package',
            'color' => 'gray',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Llegada a Cochabamba
    |--------------------------------------------------------------------------
    */

    if ($unidad->fecha_llegada) {

        $eventos->push([
            'fecha' => $unidad->fecha_llegada,
            'titulo' => 'Llegada a Cochabamba',
            'descripcion' =>
                'La unidad fue recibida físicamente en el punto de preparación.',
            'ubicacion' =>
                $unidad->almacenActual?->nombre,
            'icono' => 'warehouse',
            'color' => 'blue',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Revisiones técnicas
    |--------------------------------------------------------------------------
    */

    if ($unidad->revisionesTecnicas->count()) {

        foreach ($unidad->revisionesTecnicas as $revisionTecnica) {

            $descripcionRevision = match ($revisionTecnica->resultado) {
                \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA =>
                    'La unidad completó satisfactoriamente el checklist técnico.',

                \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_REQUIERE_PREPARACION =>
                    $revisionTecnica->servicio_requerido
                        ? 'La revisión detectó trabajo pendiente: ' . $revisionTecnica->servicio_requerido
                        : 'La revisión detectó fallas que requieren preparación adicional.',

                default =>
                    'La revisión quedó incompleta y requiere continuar con el checklist técnico.',
            };

            $eventos->push([
                'fecha' => $revisionTecnica->fecha_revision,
                'titulo' => 'Revisión técnica',
                'descripcion' => $descripcionRevision,
                'ubicacion' => 'Cochabamba',
                'icono' => 'search',
                'color' => match ($revisionTecnica->resultado) {
                    \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_APROBADA => 'green',
                    \App\Models\RevisionTecnicaUnidadAdquirida::RESULTADO_REQUIERE_PREPARACION => 'yellow',
                    default => 'blue',
                },
            ]);
        }

    } elseif ($unidad->fecha_revision) {

        /* Compatibilidad con revisiones registradas antes del historial técnico. */
        $eventos->push([
            'fecha' => $unidad->fecha_revision,
            'titulo' => 'Revisión técnica preliminar',
            'descripcion' =>
                $unidad->requiere_servicio
                    ? 'La revisión determinó que la unidad requiere preparación adicional.'
                    : 'La unidad fue revisada técnicamente.',
            'ubicacion' => 'Cochabamba',
            'icono' => 'search',
            'color' =>
                $unidad->requiere_servicio
                    ? 'yellow'
                    : 'blue',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Reaperturas de preparación
    |--------------------------------------------------------------------------
    */

    foreach (($reaperturasPreparacion ?? collect()) as $reapertura) {
        $motivoReapertura =
            $reapertura->datos_nuevos['motivo']
            ?? 'La preparación fue reabierta para una nueva verificación.';

        $descripcionReapertura =
            'Motivo: ' . $motivoReapertura;

        if ($reapertura->usuario) {
            $descripcionReapertura .=
                ' · Responsable: ' . $reapertura->usuario->name . '.';
        }

        $eventos->push([
            'fecha' => $reapertura->fecha_evento,
            'titulo' => 'Preparación reabierta',
            'descripcion' => $descripcionReapertura,
            'ubicacion' => 'Cochabamba',
            'icono' => 'wrench',
            'color' => 'yellow',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Intervenciones
    |--------------------------------------------------------------------------
    */

    foreach ($unidad->intervenciones as $intervencion) {

        $descripcion =
            $intervencion->descripcion;

        if ($intervencion->producto) {

            $descripcion .=
                ' · ' .
                $intervencion->producto->nombre;
        }

        if ($intervencion->monto_bob !== null) {

            $descripcion .=
                ' · Bs ' .
                number_format(
                    (float) $intervencion->monto_bob,
                    2
                );
        }


        $eventos->push([
            'fecha' =>
                $intervencion->fecha_inicio
                ?? $intervencion->created_at,

            'titulo' =>
                $intervencion->tipo ===
                \App\Models\IntervencionUnidadAdquirida::TIPO_COMPONENTE
                    ? 'Componente incorporado'
                    : 'Servicio técnico',

            'descripcion' =>
                $descripcion,

            'ubicacion' =>
                'Cochabamba',

            'icono' =>
                $intervencion->tipo ===
                \App\Models\IntervencionUnidadAdquirida::TIPO_COMPONENTE
                    ? 'package'
                    : 'wrench',

            'color' =>
                'yellow',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Lista para envío
    |--------------------------------------------------------------------------
    */

    if ($unidad->fecha_lista_envio) {

        $eventos->push([
            'fecha' =>
                $unidad->fecha_lista_envio,

            'titulo' =>
                'Unidad lista para envío',

            'descripcion' =>
                'La preparación técnica fue completada y la unidad quedó habilitada para el traslado a Oruro.',

            'ubicacion' =>
                'Cochabamba',

            'icono' =>
                'check',

            'color' =>
                'green',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Preparación del envío
    |--------------------------------------------------------------------------
    */

    if ($envio?->fecha_preparacion) {

        $eventos->push([
            'fecha' =>
                $envio->fecha_preparacion,

            'titulo' =>
                'Incluida en envío ' .
                $envio->codigo,

            'descripcion' =>
                'La unidad fue incluida en un envío preparado para su traslado a Oruro.',

            'ubicacion' =>
                $envio->almacenOrigen?->nombre
                ?? 'Cochabamba',

            'icono' =>
                'package',

            'color' =>
                'blue',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Despacho a Oruro
    |--------------------------------------------------------------------------
    */

    if ($envio?->fecha_despacho) {

        $descripcion =
            'La unidad fue despachada desde Cochabamba hacia Oruro.';


        if ($envio->transportista) {

            $descripcion .=
                ' Transportista: ' .
                $envio->transportista .
                '.';
        }


        if ($envio->numero_guia) {

            $descripcion .=
                ' Guía: ' .
                $envio->numero_guia .
                '.';
        }


        $eventos->push([
            'fecha' =>
                $envio->fecha_despacho,

            'titulo' =>
                'Despacho hacia Oruro',

            'descripcion' =>
                $descripcion,

            'ubicacion' =>
                $envio->almacenOrigen?->nombre
                ?? 'Cochabamba',

            'icono' =>
                'truck',

            'color' =>
                'yellow',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Resultado de recepción
    |--------------------------------------------------------------------------
    */

    if (
        $detalleEnvio &&
        $detalleEnvio->estado_recepcion ===
            \App\Models\EnvioImportacionUnidad::ESTADO_FALTANTE
    ) {

        $eventos->push([
            'fecha' =>
                $detalleEnvio->updated_at,

            'titulo' =>
                'Unidad reportada como faltante',

            'descripcion' =>
                $detalleEnvio->observacion_recepcion
                ?? 'La unidad no fue encontrada durante la recepción.',

            'ubicacion' =>
                'Oruro',

            'icono' =>
                'x',

            'color' =>
                'red',
        ]);
    }


    if (
        $detalleEnvio &&
        $detalleEnvio->estado_recepcion ===
            \App\Models\EnvioImportacionUnidad::ESTADO_INCIDENCIA
    ) {

        $eventos->push([
            'fecha' =>
                $detalleEnvio->fecha_recepcion
                ?? $detalleEnvio->updated_at,

            'titulo' =>
                'Recepción con incidencia',

            'descripcion' =>
                $detalleEnvio->observacion_recepcion
                ?? 'La unidad llegó a Oruro con una incidencia registrada.',

            'ubicacion' =>
                $envio?->almacenDestino?->nombre
                ?? 'Oruro',

            'icono' =>
                'wrench',

            'color' =>
                'yellow',
        ]);
    }


    if (
        $detalleEnvio &&
        $detalleEnvio->estado_recepcion ===
            \App\Models\EnvioImportacionUnidad::ESTADO_RECIBIDA
        &&
        $detalleEnvio->fecha_recepcion
    ) {

        $eventos->push([
            'fecha' =>
                $detalleEnvio->fecha_recepcion,

            'titulo' =>
                'Recepción física en Oruro',

            'descripcion' =>
                $detalleEnvio->observacion_recepcion
                ?: 'La unidad fue recibida físicamente en Oruro.',

            'ubicacion' =>
                $envio?->almacenDestino?->nombre
                ?? 'Oruro',

            'icono' =>
                'warehouse',

            'color' =>
                'green',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Incorporación al inventario
    |--------------------------------------------------------------------------
    */

    if ($unidad->incorporacionInventario) {

        $incorporacion =
            $unidad->incorporacionInventario;


        $eventos->push([
            'fecha' =>
                $incorporacion->fecha_incorporacion,

            'titulo' =>
                'Incorporación al inventario',

            'descripcion' =>
                $unidad->equipo
                    ? 'La unidad fue convertida en equipo oficial de inventario con código ' .
                        $unidad->equipo->codigo_interno .
                        '.'
                    : 'La unidad fue incorporada formalmente al inventario.',

            'ubicacion' =>
                $unidad->equipo?->almacenActual?->nombre
                ?? 'Oruro',

            'icono' =>
                'check',

            'color' =>
                'green',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Orden cronológico
    |--------------------------------------------------------------------------
    */

    $eventos =
        $eventos
            ->filter(
                fn ($evento) =>
                    $evento['fecha'] !== null
            )
            ->sortBy(
                'fecha'
            )
            ->values();


    $clasesColor = [
        'gray' =>
            'bg-slate-100 text-slate-600',

        'blue' =>
            'bg-blue-100 text-blue-700',

        'yellow' =>
            'bg-yellow-100 text-yellow-700',

        'green' =>
            'bg-green-100 text-green-700',

        'red' =>
            'bg-red-100 text-red-700',
    ];
@endphp


<x-ui.card>

    {{-- Header --}}
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
                name="history"
                size="20"
            />
        </div>


        <div>

            <h3 class="text-lg font-bold text-slate-900">
                Trazabilidad de la unidad
            </h3>

            <p class="mt-1 text-sm text-slate-500">
                Historial cronológico desde la adquisición hasta su incorporación al inventario.
            </p>

        </div>

    </div>


    {{-- Timeline --}}
    <div class="mt-7">

        @forelse($eventos as $evento)

            <div
                class="
                    relative
                    flex
                    gap-4
                    pb-8
                    last:pb-0
                "
            >

                {{-- Línea --}}
                @if(!$loop->last)

                    <div
                        class="
                            absolute
                            left-[19px]
                            top-10
                            h-[calc(100%-2rem)]
                            w-px
                            bg-slate-200
                        "
                    ></div>

                @endif


                {{-- Icono --}}
                <div
                    class="
                        relative
                        z-10
                        flex
                        h-10
                        w-10
                        shrink-0
                        items-center
                        justify-center
                        rounded-full
                        {{
                            $clasesColor[
                                $evento['color']
                            ]
                            ??
                            $clasesColor['gray']
                        }}
                    "
                >
                    <x-ui.icon
                        :name="$evento['icono']"
                        size="17"
                    />
                </div>


                {{-- Contenido --}}
                <div
                    class="
                        min-w-0
                        flex-1
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
                            gap-2
                            sm:flex-row
                            sm:items-start
                            sm:justify-between
                        "
                    >

                        <div>

                            <h4 class="font-semibold text-slate-900">
                                {{ $evento['titulo'] }}
                            </h4>

                            <p class="mt-1 text-sm text-slate-600">
                                {{ $evento['descripcion'] }}
                            </p>


                            @if($evento['ubicacion'])

                                <div
                                    class="
                                        mt-2
                                        flex
                                        items-center
                                        gap-1.5
                                        text-xs
                                        text-slate-500
                                    "
                                >
                                    <x-ui.icon
                                        name="warehouse"
                                        size="14"
                                    />

                                    {{ $evento['ubicacion'] }}
                                </div>

                            @endif

                        </div>


                        <time
                            class="
                                shrink-0
                                text-xs
                                font-medium
                                text-slate-500
                            "
                        >
                            {{
                                $evento['fecha']
                                    ->format(
                                        'd/m/Y H:i'
                                    )
                            }}
                        </time>

                    </div>

                </div>

            </div>

        @empty

            <div
                class="
                    rounded-xl
                    border
                    border-slate-200
                    bg-slate-50
                    p-8
                    text-center
                "
            >

                <x-ui.icon
                    name="history"
                    size="26"
                    class="mx-auto text-slate-300"
                />

                <p class="mt-3 font-semibold text-slate-700">
                    Sin eventos registrados
                </p>

            </div>

        @endforelse

    </div>

</x-ui.card>