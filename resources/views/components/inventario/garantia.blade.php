@php
    $detalleGarantiaActual = $equipo
        ->detallesVentas
        ->filter(function ($detalle) {
            return $detalle->garantia
                && $detalle->venta
                && $detalle->venta->estado !== 'ANULADA';
        })
        ->sortByDesc(function ($detalle) {
            return $detalle->venta?->fecha_venta?->timestamp ?? 0;
        })
        ->first();

    $garantiaActual =
        $detalleGarantiaActual?->garantia;

    $casoActivo =
        $equipo->casosGarantia
            ->first(function ($caso) {
                return in_array(
                    strtoupper($caso->estado ?? ''),
                    [
                        'ABIERTO',
                        'DIAGNOSTICADO',
                        'EN_PROCESO',
                    ],
                    true
                );
            });

    $puedeAbrirCaso =
        $garantiaActual
        && $garantiaActual->estaVigente()
        && !$casoActivo
        && auth()->user()?->tienePermiso(
            'garantias.registrar'
        );

    $puedeGestionarGarantias =
        auth()->user()?->tienePermiso(
            'garantias.gestionar'
        );
@endphp

<section
    class="
    rounded-2xl
    border
    border-slate-200
    bg-white
    shadow-sm
    "
>


    <div
        class="
        flex
        flex-col
        gap-3
        border-b
        border-slate-200
        px-6
        py-5
        sm:flex-row
        sm:items-center
        sm:justify-between
        "
    >

        <div>

            <h2 class="font-semibold text-slate-950">

                Garantía y soporte

            </h2>


            <p class="mt-1 text-sm text-slate-500">

                Seguimiento de casos, diagnósticos e intervenciones del equipo.

            </p>

        </div>



        @if($equipo->casosGarantia && $equipo->casosGarantia->count())


            <span
                class="
                inline-flex
                w-fit
                items-center
                rounded-full
                bg-blue-50
                px-3
                py-1.5
                text-xs
                font-semibold
                text-blue-700
                "
            >

                {{ $equipo->casosGarantia->count() }}
                caso(s)

            </span>


        @endif


    </div>





    <div class="p-6">

        @if($garantiaActual)

            <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5">

                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                    <div>

                        <p class="text-sm font-semibold text-slate-900">
                            Garantía {{ $garantiaActual->numero }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Vigencia:
                            {{ $garantiaActual->fecha_inicio?->format('d/m/Y') ?? '-' }}
                            al
                            {{ $garantiaActual->fecha_fin?->format('d/m/Y') ?? '-' }}
                        </p>

                    </div>


                    @if($garantiaActual->estaVigente())

                        <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            VIGENTE
                        </span>

                    @else

                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            NO VIGENTE
                        </span>

                    @endif

                </div>


                @error('garantia')

                    <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700">
                        {{ $message }}
                    </div>

                @enderror


                @if($casoActivo)

                    <div class="mt-4 rounded-xl bg-amber-50 p-4">

                        <p class="text-sm font-semibold text-amber-800">
                            Existe un caso de postventa activo.
                        </p>

                        <p class="mt-1 text-sm text-amber-700">
                            {{ $casoActivo->numero }}
                            ·
                            {{ $casoActivo->estado }}
                        </p>

                    </div>

                @elseif($puedeAbrirCaso)

                    <details
                        class="mt-5 rounded-xl border border-blue-200 bg-blue-50"
                        @if(
                            $errors->has('garantia')
                            || $errors->has('motivo_cliente')
                            || $errors->has('observacion')
                        )
                            open
                        @endif
                    >

                        <summary class="cursor-pointer px-5 py-4 font-semibold text-blue-800">
                            Abrir caso de garantía
                        </summary>

                        <form
                            method="POST"
                            action="{{ route(
                                'garantias.casos.store',
                                $garantiaActual
                            ) }}"
                            class="space-y-5 border-t border-blue-200 p-5"
                        >

                            @csrf

                            <div>

                                <label
                                    for="motivo_cliente"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Problema reportado por el cliente
                                </label>

                                <textarea
                                    id="motivo_cliente"
                                    name="motivo_cliente"
                                    rows="4"
                                    required
                                    maxlength="3000"
                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Ej.: El equipo dejó de encender después de dos semanas de uso."
                                >{{ old('motivo_cliente') }}</textarea>

                                @error('motivo_cliente')

                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            <div>

                                <label
                                    for="observacion"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Observación interna
                                </label>

                                <textarea
                                    id="observacion"
                                    name="observacion"
                                    rows="3"
                                    maxlength="3000"
                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Estado físico al recibir, accesorios entregados, detalles adicionales..."
                                >{{ old('observacion') }}</textarea>

                                @error('observacion')

                                    <p class="mt-2 text-sm font-medium text-red-600">
                                        {{ $message }}
                                    </p>

                                @enderror

                            </div>


                            <div class="rounded-xl bg-white p-4 text-sm text-slate-600">

                                <p class="font-semibold text-slate-800">
                                    Importante
                                </p>

                                <p class="mt-1">
                                    Abrir el caso no devuelve el equipo al inventario
                                    ni modifica su estado de venta.
                                </p>

                            </div>


                            <div class="flex justify-end">

                                <button
                                    type="submit"
                                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    Registrar caso
                                </button>

                            </div>

                        </form>

                    </details>

                @elseif(
                    auth()->user()?->tienePermiso('garantias.registrar')
                    && !$garantiaActual->estaVigente()
                )

                    <div class="mt-4 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
                        Esta garantía ya no se encuentra vigente.
                    </div>

                @endif

            </div>

        @endif



        @if($equipo->casosGarantia && $equipo->casosGarantia->count())



            <div class="space-y-6">



                @foreach($equipo->casosGarantia as $caso)



                    <div
                        class="
                        rounded-2xl
                        border
                        border-slate-200
                        bg-slate-50
                        p-6
                        "
                    >





                        {{-- Cabecera --}}


                        <div
                            class="
                            flex
                            flex-col
                            gap-4
                            sm:flex-row
                            sm:items-start
                            sm:justify-between
                            "
                        >


                            <div>


                                <div class="flex items-center gap-3">


                                    <div
                                        class="
                                        flex
                                        h-10
                                        w-10
                                        items-center
                                        justify-center
                                        rounded-xl
                                        bg-blue-100
                                        text-blue-600
                                        "
                                    >

                                        <x-ui.icon
                                            name="shield"
                                            size="22"
                                        />

                                    </div>


                                    <div>


                                        <p
                                            class="
                                            font-semibold
                                            text-slate-900
                                            "
                                        >

                                            {{ $caso->numero }}

                                        </p>


                                        <p
                                            class="
                                            text-sm
                                            text-slate-500
                                            "
                                        >

                                            {{ $caso->tipo_caso ?? 'Caso de garantía' }}

                                        </p>


                                    </div>


                                </div>


                            </div>






                            @php

                                $estado =
                                strtoupper($caso->estado ?? '');

                            @endphp



                            <span
                                class="
                                inline-flex
                                w-fit
                                rounded-full
                                px-3
                                py-1
                                text-xs
                                font-semibold
                                "
                                @class([

                                    'bg-emerald-100 text-emerald-700'
                                    =>
                                    in_array($estado,['CERRADO','FINALIZADO']),


                                    'bg-amber-100 text-amber-700'
                                    =>
                                    in_array($estado,['ABIERTO','PENDIENTE','DIAGNOSTICO','DIAGNOSTICADO','EN_PROCESO']),


                                    'bg-slate-100 text-slate-700'
                                    =>
                                    !in_array($estado,['CERRADO','FINALIZADO','ABIERTO','PENDIENTE','DIAGNOSTICO','DIAGNOSTICADO','EN_PROCESO'])

                                ])
                            >

                                {{ $caso->estado }}

                            </span>



                        </div>









                        {{-- Fechas --}}


                        <div
                            class="
                            mt-6
                            grid
                            gap-4
                            sm:grid-cols-2
                            "
                        >



                            <div
                                class="
                                rounded-xl
                                bg-white
                                p-4
                                "
                            >

                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Apertura

                                </p>


                                <p class="mt-2 font-medium text-slate-800">

                                    {{ $caso->fecha_apertura?->format('d/m/Y') ?? '—' }}

                                </p>


                            </div>




                            <div
                                class="
                                rounded-xl
                                bg-white
                                p-4
                                "
                            >

                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Cierre

                                </p>


                                <p class="mt-2 font-medium text-slate-800">

                                    {{ $caso->fecha_cierre?->format('d/m/Y') ?? 'Pendiente' }}

                                </p>


                            </div>



                        </div>










                        {{-- Información del caso --}}



                        @if($caso->motivo_cliente)


                            <div class="mt-6">


                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Motivo del cliente

                                </p>


                                <p class="mt-2 text-sm text-slate-700">

                                    {{ $caso->motivo_cliente }}

                                </p>


                            </div>


                        @endif







                        @if($caso->diagnostico_final)


                            <div class="mt-5">


                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Diagnóstico final

                                </p>


                                <p class="mt-2 text-sm text-slate-700">

                                    {{ $caso->diagnostico_final }}

                                </p>


                            </div>


                        @endif







                        @if($caso->resolucion)


                            <div class="mt-5">


                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Resolución

                                </p>


                                <p class="mt-2 text-sm text-slate-700">

                                    {{ $caso->resolucion }}

                                </p>


                            </div>


                        @endif







                        {{-- Intervenciones --}}


                        @if($caso->intervenciones && $caso->intervenciones->count())


                            <div
                                class="
                                mt-6
                                rounded-xl
                                border
                                border-slate-200
                                bg-white
                                p-5
                                "
                            >


                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Historial de intervenciones

                                </p>




                                <div class="mt-4 space-y-4">


                                    @foreach($caso->intervenciones as $intervencion)


                                        <div
                                            class="
                                            border-l-2
                                            border-blue-200
                                            pl-4
                                            "
                                        >


                                            <p class="font-medium text-slate-800">

                                                {{ $intervencion->tipo_intervencion }}

                                            </p>


                                            <p class="mt-1 text-sm text-slate-600">

                                                {{ $intervencion->descripcion }}

                                            </p>


                                            @if($intervencion->resultado)

                                                <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">
                                                    <span class="font-semibold text-slate-700">
                                                        Resultado:
                                                    </span>
                                                    {{ $intervencion->resultado }}
                                                </p>

                                            @endif



                                            <p class="mt-2 text-xs text-slate-400">


                                                {{ $intervencion->fecha_intervencion?->format('d/m/Y') }}

                                                @if($intervencion->usuario)

                                                    ·
                                                    {{ $intervencion->usuario->name }}

                                                @endif


                                            </p>



                                        </div>


                                    @endforeach


                                </div>



                            </div>


                        @endif








                        @php
                            $estadoCaso =
                                strtoupper(
                                    $caso->estado ?? ''
                                );

                            $esFormularioCasoActual =
                                (int) old(
                                    '_caso_id',
                                    0
                                ) === (int) $caso->id;

                            $puedeDiagnosticar =
                                $puedeGestionarGarantias
                                && $estadoCaso === 'ABIERTO';

                            $puedeIntervenir =
                                $puedeGestionarGarantias
                                && in_array(
                                    $estadoCaso,
                                    [
                                        'DIAGNOSTICADO',
                                        'EN_PROCESO',
                                    ],
                                    true
                                );

                            $puedeCerrarCaso =
                                $puedeGestionarGarantias
                                && in_array(
                                    $estadoCaso,
                                    [
                                        'DIAGNOSTICADO',
                                        'EN_PROCESO',
                                    ],
                                    true
                                );
                        @endphp


                        @if(
                            $puedeGestionarGarantias
                            && $estadoCaso !== 'CERRADO'
                        )

                            <div class="mt-6 rounded-2xl border border-blue-100 bg-blue-50/60 p-5">

                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                    <div>
    <p class="font-semibold text-slate-900">
        Gestión técnica del caso
    </p>
</div>

                                    <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700 shadow-sm">
                                        {{ $estadoCaso }}
                                    </span>

                                </div>


                                @if(
                                    $esFormularioCasoActual
                                    && $errors->has('garantia_gestion')
                                )

                                    <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700">
                                        {{ $errors->first('garantia_gestion') }}
                                    </div>

                                @endif


                                @if($puedeDiagnosticar)

                                    <details
                                        class="mt-5 rounded-xl border border-slate-200 bg-white"
                                        @if(
                                            $esFormularioCasoActual
                                            && (
                                                $errors->has('diagnostico_final')
                                                || $errors->has('garantia_gestion')
                                            )
                                        )
                                            open
                                        @endif
                                    >

                                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800">
                                            Registrar diagnóstico
                                        </summary>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'garantias.casos.diagnostico',
                                                $caso
                                            ) }}"
                                            class="space-y-4 border-t border-slate-200 p-4"
                                        >

                                            @csrf

                                            <input
                                                type="hidden"
                                                name="_caso_id"
                                                value="{{ $caso->id }}"
                                            >

                                            <div>

                                                <label
                                                    for="diagnostico_final_{{ $caso->id }}"
                                                    class="block text-sm font-semibold text-slate-700"
                                                >
                                                    Diagnóstico técnico
                                                </label>

                                                <textarea
                                                    id="diagnostico_final_{{ $caso->id }}"
                                                    name="diagnostico_final"
                                                    rows="4"
                                                    required
                                                    maxlength="5000"
                                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Describe la falla encontrada y el diagnóstico técnico."
                                                >{{ $esFormularioCasoActual ? old('diagnostico_final') : '' }}</textarea>

                                                @if(
                                                    $esFormularioCasoActual
                                                    && $errors->has('diagnostico_final')
                                                )

                                                    <p class="mt-2 text-sm font-medium text-red-600">
                                                        {{ $errors->first('diagnostico_final') }}
                                                    </p>

                                                @endif

                                            </div>

                                            <div class="flex justify-end">

                                                <button
                                                    type="submit"
                                                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                                                >
                                                    Guardar diagnóstico
                                                </button>

                                            </div>

                                        </form>

                                    </details>

                                @endif


                                @if($puedeIntervenir)

                                    <details
                                        class="mt-4 rounded-xl border border-slate-200 bg-white"
                                        @if(
                                            $esFormularioCasoActual
                                            && (
                                                $errors->has('tipo_intervencion')
                                                || $errors->has('descripcion')
                                                || $errors->has('resultado')
                                                || $errors->has('garantia_gestion')
                                            )
                                        )
                                            open
                                        @endif
                                    >

                                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-800">
                                            Registrar intervención
                                        </summary>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'garantias.casos.intervenciones.store',
                                                $caso
                                            ) }}"
                                            class="space-y-4 border-t border-slate-200 p-4"
                                        >

                                            @csrf

                                            <input
                                                type="hidden"
                                                name="_caso_id"
                                                value="{{ $caso->id }}"
                                            >

                                            <div>

                                                <label
                                                    for="tipo_intervencion_{{ $caso->id }}"
                                                    class="block text-sm font-semibold text-slate-700"
                                                >
                                                    Tipo de intervención
                                                </label>

                                                <input
                                                    id="tipo_intervencion_{{ $caso->id }}"
                                                    type="text"
                                                    name="tipo_intervencion"
                                                    required
                                                    maxlength="100"
                                                    value="{{ $esFormularioCasoActual ? old('tipo_intervencion') : '' }}"
                                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Ej.: REPARACIÓN, PRUEBA, AJUSTE..."
                                                >

                                                @if(
                                                    $esFormularioCasoActual
                                                    && $errors->has('tipo_intervencion')
                                                )

                                                    <p class="mt-2 text-sm font-medium text-red-600">
                                                        {{ $errors->first('tipo_intervencion') }}
                                                    </p>

                                                @endif

                                            </div>


                                            <div>

                                                <label
                                                    for="descripcion_{{ $caso->id }}"
                                                    class="block text-sm font-semibold text-slate-700"
                                                >
                                                    Trabajo realizado
                                                </label>

                                                <textarea
                                                    id="descripcion_{{ $caso->id }}"
                                                    name="descripcion"
                                                    rows="3"
                                                    required
                                                    maxlength="5000"
                                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Describe la intervención realizada."
                                                >{{ $esFormularioCasoActual ? old('descripcion') : '' }}</textarea>

                                                @if(
                                                    $esFormularioCasoActual
                                                    && $errors->has('descripcion')
                                                )

                                                    <p class="mt-2 text-sm font-medium text-red-600">
                                                        {{ $errors->first('descripcion') }}
                                                    </p>

                                                @endif

                                            </div>


                                            <div>

                                                <label
                                                    for="resultado_{{ $caso->id }}"
                                                    class="block text-sm font-semibold text-slate-700"
                                                >
                                                    Resultado
                                                </label>

                                                <textarea
                                                    id="resultado_{{ $caso->id }}"
                                                    name="resultado"
                                                    rows="2"
                                                    maxlength="5000"
                                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                    placeholder="Resultado obtenido después de la intervención."
                                                >{{ $esFormularioCasoActual ? old('resultado') : '' }}</textarea>

                                                @if(
                                                    $esFormularioCasoActual
                                                    && $errors->has('resultado')
                                                )

                                                    <p class="mt-2 text-sm font-medium text-red-600">
                                                        {{ $errors->first('resultado') }}
                                                    </p>

                                                @endif

                                            </div>


                                            <div class="flex justify-end">

                                                <button
                                                    type="submit"
                                                    class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"
                                                >
                                                    Registrar intervención
                                                </button>

                                            </div>

                                        </form>

                                    </details>

                                @endif


                                @if($puedeCerrarCaso)

                                    <details
                                        class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50"
                                        @if(
                                            $esFormularioCasoActual
                                            && $errors->has('resolucion')
                                        )
                                            open
                                        @endif
                                    >

                                        <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-emerald-800">
                                            Cerrar caso
                                        </summary>

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'garantias.casos.cerrar',
                                                $caso
                                            ) }}"
                                            class="space-y-4 border-t border-emerald-200 p-4"
                                        >

                                            @csrf

                                            <input
                                                type="hidden"
                                                name="_caso_id"
                                                value="{{ $caso->id }}"
                                            >

                                            <div>

                                                <label
                                                    for="resolucion_{{ $caso->id }}"
                                                    class="block text-sm font-semibold text-slate-700"
                                                >
                                                    Resolución final
                                                </label>

                                                <textarea
                                                    id="resolucion_{{ $caso->id }}"
                                                    name="resolucion"
                                                    rows="3"
                                                    required
                                                    maxlength="5000"
                                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"
                                                    placeholder="Ej.: Equipo reparado, probado y entregado funcionando correctamente."
                                                >{{ $esFormularioCasoActual ? old('resolucion') : '' }}</textarea>

                                                @if(
                                                    $esFormularioCasoActual
                                                    && $errors->has('resolucion')
                                                )

                                                    <p class="mt-2 text-sm font-medium text-red-600">
                                                        {{ $errors->first('resolucion') }}
                                                    </p>

                                                @endif

                                            </div>


                                            <div class="rounded-xl bg-white p-3 text-sm text-slate-600">
                                          
                                            </div>


                                            <div class="flex justify-end">

                                                <button
                                                    type="submit"
                                                    class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                                                >
                                                    Cerrar caso
                                                </button>

                                            </div>

                                        </form>

                                    </details>

                                @endif

                            </div>

                        @elseif(
                            $puedeGestionarGarantias
                            && $estadoCaso === 'CERRADO'
                        )

                        @endif


                        @if($caso->observacion)


                            <div
                                class="
                                mt-5
                                rounded-xl
                                bg-white
                                p-4
                                "
                            >

                                <p
                                    class="
                                    text-xs
                                    font-semibold
                                    uppercase
                                    tracking-wide
                                    text-slate-400
                                    "
                                >

                                    Observación

                                </p>


                                <p class="mt-2 text-sm text-slate-600">

                                    {{ $caso->observacion }}

                                </p>


                            </div>


                        @endif





                    </div>




                @endforeach




            </div>




        @else



            <div
                class="
                py-10
                text-center
                "
            >


                <div
                    class="
                    mx-auto
                    flex
                    h-14
                    w-14
                    items-center
                    justify-center
                    rounded-full
                    bg-slate-100
                    text-slate-400
                    "
                >

                    <x-ui.icon
                        name="shield"
                        size="26"
                    />

                </div>



                <p
                    class="
                    mt-4
                    font-semibold
                    text-slate-800
                    "
                >

                    Sin casos de garantía registrados

                </p>


                <p class="mt-2 text-sm text-slate-500">

                    El equipo no presenta reclamos ni procesos asociados.

                </p>



            </div>




        @endif




    </div>


</section>
