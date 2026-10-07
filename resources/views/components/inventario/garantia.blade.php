@props([
    'equipo',
    'equiposReemplazo' => null,
    'metodos' => null,
    'resumenes' => null,
    'garantia' => null,
    'casoEnfocadoId' => null,
])

@php
    $equiposReemplazo =
        $equiposReemplazo
        ?? collect();

    $metodos =
        $metodos
        ?? collect();

    $resumenes =
        $resumenes
        ?? collect();

    $modoCaso = (int) ($casoEnfocadoId ?? 0) > 0;

    $detalleGarantiaActual =
        $garantia?->detalleVenta
        ?? $equipo
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
        $garantia
        ?? $detalleGarantiaActual?->garantia;

    $casosVisibles =
        $casoEnfocadoId
            ? $equipo->casosGarantia
                ->where('id', (int) $casoEnfocadoId)
                ->values()
            : $equipo->casosGarantia;

    $estadoCasoTexto = function (?string $estado): string {
        return match (strtoupper((string) $estado)) {
            'ABIERTO' => 'Abierto',
            'DIAGNOSTICADO' => 'Diagnosticado',
            'EN_PROCESO' => 'En proceso',
            'CERRADO' => 'Cerrado',
            'FINALIZADO' => 'Finalizado',
            'PENDIENTE' => 'Pendiente',
            'DIAGNOSTICO' => 'En diagnóstico',
            default => $estado
                ? ucfirst(mb_strtolower(str_replace('_', ' ', $estado)))
                : 'Sin estado',
        };
    };

    $tipoCasoTexto = function (?string $tipo): string {
        return match (strtoupper((string) $tipo)) {
            'GARANTIA' => 'Caso de garantía',
            default => $tipo
                ? ucfirst(mb_strtolower(str_replace('_', ' ', $tipo)))
                : 'Caso de garantía',
        };
    };

    $tipoIntervencionTexto = function (?string $tipo): string {
        return match (strtoupper((string) $tipo)) {
            'DIAGNOSTICO_COMPLEMENTARIO' => 'Diagnóstico complementario',
            'PRUEBA' => 'Prueba',
            'AJUSTE' => 'Ajuste',
            'REPARACION' => 'Reparación',
            'MANTENIMIENTO' => 'Mantenimiento',
            'OTRO' => 'Otro',
            default => $tipo
                ? ucfirst(mb_strtolower(str_replace('_', ' ', $tipo)))
                : 'Intervención',
        };
    };

    $casoActivo =
        $casosVisibles
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

    $puedeAutorizarCambio =
        auth()->user()?->tienePermiso(
            'garantias.autorizar_cambio'
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

                {{ $modoCaso ? 'Seguimiento del caso' : 'Garantía y soporte' }}

            </h2>


            <p class="mt-1 text-sm text-slate-500">

                {{ $modoCaso
                    ? 'Diagnóstico, intervenciones, resolución y movimientos asociados al caso.'
                    : 'Seguimiento de casos, diagnósticos e intervenciones del equipo.'
                }}

            </p>

        </div>



        @if(!$modoCaso && $casosVisibles && $casosVisibles->count())


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

                {{ $casosVisibles->count() }}
                caso(s)

            </span>


        @endif


    </div>





    <div class="p-6">

        @if($garantiaActual && !$modoCaso)

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
                            Vigente
                        </span>

                    @else

                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            No vigente
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
                            {{ $estadoCasoTexto($casoActivo->estado) }}
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



        @if($casosVisibles && $casosVisibles->count())



            <div class="space-y-6">



                @foreach($casosVisibles as $caso)



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

                                            {{ $tipoCasoTexto($caso->tipo_caso) }}

                                        </p>


                                    </div>


                                </div>


                            </div>






                            @php

                                $estado =
                                strtoupper($caso->estado ?? '');

                            @endphp



                            <div class="flex flex-wrap items-center justify-end gap-2">

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

                                    {{ $estadoCasoTexto($caso->estado) }}

                                </span>

                                @if((int) ($casoEnfocadoId ?? 0) !== (int) $caso->id)

                                    <a
                                        href="{{ route('garantias.casos.show', $caso) }}"
                                        class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                    >
                                        Abrir ficha
                                    </a>

                                @endif

                            </div>



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

                                                {{ $tipoIntervencionTexto($intervencion->tipo_intervencion) }}

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

                            $cambioEquipo =
                                $caso->cambioEquipo;

                            $puedeCambiarEquipo =
                                $puedeAutorizarCambio
                                && !$cambioEquipo
                                && in_array(
                                    $estadoCaso,
                                    [
                                        'DIAGNOSTICADO',
                                        'EN_PROCESO',
                                    ],
                                    true
                                );

                            $errorCambioActual =
                                $esFormularioCasoActual
                                && (
                                    $errors->has('equipo_entrante_id')
                                    || $errors->has('motivo')
                                    || $errors->has('observacion')
                                    || $errors->has('garantia_cambio')
                                );
                        @endphp


                        {{-- Cambio de equipo por garantía --}}

                        @if($cambioEquipo)

                            @php
                                $tieneAjusteEconomico =
                                    $cambioEquipo->valor_original_snapshot !== null
                                    && $cambioEquipo->valor_reemplazo_snapshot !== null
                                    && $cambioEquipo->diferencia_snapshot !== null
                                    && $cambioEquipo->moneda_ajuste !== null
                                    && $cambioEquipo->tipo_ajuste !== null
                                    && $cambioEquipo->estado_ajuste !== null;

                                $tipoAjusteTexto = match (
                                    $cambioEquipo->tipo_ajuste
                                ) {
                                    'COBRO_CLIENTE' =>
                                        'Cobro al cliente',

                                    'SALDO_FAVOR_CLIENTE' =>
                                        'Saldo a favor del cliente',

                                    'SIN_DIFERENCIA' =>
                                        'Sin diferencia',

                                    default =>
                                        $cambioEquipo->tipo_ajuste
                                            ? str_replace(
                                                '_',
                                                ' ',
                                                $cambioEquipo->tipo_ajuste
                                            )
                                            : 'No disponible',
                                };

                                $estadoAjusteTexto = match (
                                    $cambioEquipo->estado_ajuste
                                ) {
                                    'PENDIENTE' =>
                                        'Pendiente',

                                    'LIQUIDADO' =>
                                        'Liquidado',

                                    default =>
                                        $cambioEquipo->estado_ajuste
                                            ? str_replace(
                                                '_',
                                                ' ',
                                                $cambioEquipo->estado_ajuste
                                            )
                                            : 'No disponible',
                                };

                                $estadoAjusteClase = match (
                                    $cambioEquipo->estado_ajuste
                                ) {
                                    'PENDIENTE' =>
                                        'bg-amber-100 text-amber-800',

                                    'LIQUIDADO' =>
                                        'bg-emerald-100 text-emerald-800',

                                    default =>
                                        'bg-slate-100 text-slate-700',
                                };

                                $simboloMoneda =
                                    $cambioEquipo->moneda_ajuste === 'BOB'
                                        ? 'Bs'
                                        : (
                                            $cambioEquipo->moneda_ajuste
                                            ?? ''
                                        );

                                $diferencia =
                                    $tieneAjusteEconomico
                                        ? (float) $cambioEquipo
                                            ->diferencia_snapshot
                                        : 0;

                                $signoDiferencia =
                                    $diferencia > 0
                                        ? '+'
                                        : (
                                            $diferencia < 0
                                                ? '-'
                                                : ''
                                        );
                            @endphp

                            <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">

                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                    <div>

                                        <p class="font-semibold text-amber-900">
                                            Cambio de equipo realizado
                                        </p>

                                        <p class="mt-1 text-sm text-amber-700">
                                            El equipo original se conserva en el historial de la venta
                                            y el reemplazo queda trazado en este caso.
                                        </p>

                                    </div>

                                    <span class="inline-flex w-fit rounded-full bg-white px-3 py-1 text-xs font-semibold text-amber-700 shadow-sm">
                                        CAMBIO REGISTRADO
                                    </span>

                                </div>


                                <div class="mt-5 grid gap-4 md:grid-cols-2">

                                    <div class="rounded-xl border border-amber-100 bg-white p-4">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Equipo original
                                        </p>

                                        <p class="mt-2 font-semibold text-slate-800">
                                            {{
                                                $cambioEquipo
                                                    ->equipoSaliente
                                                    ?->codigo_interno
                                                ?? 'No disponible'
                                            }}
                                        </p>

                                    </div>


                                    <div class="rounded-xl border border-amber-100 bg-white p-4">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                            Equipo entregado
                                        </p>

                                        <p class="mt-2 font-semibold text-slate-800">
                                            {{
                                                $cambioEquipo
                                                    ->equipoEntrante
                                                    ?->codigo_interno
                                                ?? 'No disponible'
                                            }}
                                        </p>

                                    </div>

                                </div>


                                @if($tieneAjusteEconomico)

                                    <div class="mt-5 rounded-xl border border-amber-200 bg-white p-4">

                                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                                            <div>

                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Ajuste económico
                                                </p>

                                                <p class="mt-1 text-sm text-slate-500">
                                                    Valores congelados al momento de autorizar el reemplazo.
                                                </p>

                                            </div>

                                            <span
                                                class="
                                                    inline-flex
                                                    w-fit
                                                    rounded-full
                                                    px-3
                                                    py-1
                                                    text-xs
                                                    font-semibold
                                                    {{ $estadoAjusteClase }}
                                                "
                                            >
                                                {{ mb_strtoupper($estadoAjusteTexto) }}
                                            </span>

                                        </div>


                                        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">

                                            <div class="rounded-lg bg-slate-50 p-3">

                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Valor original
                                                </p>

                                                <p class="mt-1 font-semibold text-slate-800">
                                                    {{ $simboloMoneda }}
                                                    {{
                                                        number_format(
                                                            (float) $cambioEquipo
                                                                ->valor_original_snapshot,
                                                            2,
                                                            ',',
                                                            '.'
                                                        )
                                                    }}
                                                </p>

                                            </div>


                                            <div class="rounded-lg bg-slate-50 p-3">

                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Valor reemplazo
                                                </p>

                                                <p class="mt-1 font-semibold text-slate-800">
                                                    {{ $simboloMoneda }}
                                                    {{
                                                        number_format(
                                                            (float) $cambioEquipo
                                                                ->valor_reemplazo_snapshot,
                                                            2,
                                                            ',',
                                                            '.'
                                                        )
                                                    }}
                                                </p>

                                            </div>


                                            <div class="rounded-lg bg-slate-50 p-3">

                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Diferencia
                                                </p>

                                                <p class="mt-1 font-semibold text-slate-800">
                                                    {{ $signoDiferencia }}{{ $simboloMoneda }}
                                                    {{
                                                        number_format(
                                                            abs($diferencia),
                                                            2,
                                                            ',',
                                                            '.'
                                                        )
                                                    }}
                                                </p>

                                            </div>


                                            <div class="rounded-lg bg-slate-50 p-3">

                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                    Tipo de ajuste
                                                </p>

                                                <p class="mt-1 font-semibold text-slate-800">
                                                    {{ $tipoAjusteTexto }}
                                                </p>

                                            </div>

                                        </div>

                                    </div>



                                   <x-inventario.ajuste-garantia
    :cambio="$cambioEquipo"
    :resumen="$resumenes->get($cambioEquipo->id)"
    :metodos="$metodos"
/>

@else

                                    <div class="mt-5 rounded-xl border border-slate-200 bg-white p-4">

                                        <p class="font-semibold text-slate-700">
                                            Ajuste económico no disponible
                                        </p>

                                        <p class="mt-1 text-sm text-slate-500">
                                            Este cambio fue registrado antes de la implementación
                                            del control económico de garantías.
                                        </p>

                                    </div>

                                @endif


                                <div class="mt-4 space-y-2 text-sm text-slate-600">

                                    <p>

                                        <span class="font-semibold text-slate-700">
                                            Fecha:
                                        </span>

                                        {{
                                            $cambioEquipo->fecha_cambio
                                                ?->format('d/m/Y H:i')
                                            ?? '—'
                                        }}

                                    </p>


                                    <p>

                                        <span class="font-semibold text-slate-700">
                                            Autorizado por:
                                        </span>

                                        {{
                                            $cambioEquipo
                                                ->autorizadoPor
                                                ?->name
                                            ?? '—'
                                        }}

                                    </p>


                                    <p>

                                        <span class="font-semibold text-slate-700">
                                            Motivo:
                                        </span>

                                        {{ $cambioEquipo->motivo }}

                                    </p>


                                    @if($cambioEquipo->observacion)

                                        <p>

                                            <span class="font-semibold text-slate-700">
                                                Observación:
                                            </span>

                                            {{ $cambioEquipo->observacion }}

                                        </p>

                                    @endif

                                </div>

                            </div>

                        @elseif($puedeCambiarEquipo)

                            <details
                                class="mt-6 rounded-2xl border border-amber-200 bg-amber-50"
                                @if($errorCambioActual)
                                    open
                                @endif
                            >

                                <summary class="cursor-pointer px-5 py-4 font-semibold text-amber-900">
                                    Autorizar cambio de equipo
                                </summary>


                                <form
                                    method="POST"
                                    action="{{ route(
                                        'garantias.casos.cambio-equipo',
                                        $caso
                                    ) }}"
                                    class="space-y-5 border-t border-amber-200 bg-white p-5"
                                >

                                    @csrf

                                    <input
                                        type="hidden"
                                        name="_caso_id"
                                        value="{{ $caso->id }}"
                                    >


                                    @if(
                                        $errorCambioActual
                                        && $errors->has('garantia_cambio')
                                    )

                                        <div class="rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700">
                                            {{ $errors->first('garantia_cambio') }}
                                        </div>

                                    @endif


                                    @php
                                        $equipoReemplazoSeleccionado =
                                            $esFormularioCasoActual
                                                ? $equiposReemplazo->firstWhere(
                                                    'id',
                                                    (int) old('equipo_entrante_id')
                                                )
                                                : null;

                                        $precioOriginalCambio =
                                            (float) (
                                                $detalleGarantiaActual?->precio_unitario
                                                ?? 0
                                            );
                                    @endphp

                                    <div>

                                        <label class="block text-sm font-semibold text-slate-700">
                                            Equipo de reemplazo
                                        </label>

                                        <input
                                            id="equipo_entrante_id_{{ $caso->id }}"
                                            type="hidden"
                                            name="equipo_entrante_id"
                                            value="{{ $equipoReemplazoSeleccionado?->id ?? '' }}"
                                        >

                                        <div
                                            id="reemplazo_seleccionado_{{ $caso->id }}"
                                            class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                                            @if(!$equipoReemplazoSeleccionado) hidden @endif
                                        >
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p
                                                        id="reemplazo_codigo_{{ $caso->id }}"
                                                        class="font-semibold text-slate-900"
                                                    >
                                                        {{ $equipoReemplazoSeleccionado?->codigo_interno }}
                                                    </p>

                                                    <p
                                                        id="reemplazo_producto_{{ $caso->id }}"
                                                        class="mt-1 text-sm text-slate-600"
                                                    >
                                                        @if($equipoReemplazoSeleccionado)
                                                            {{ $equipoReemplazoSeleccionado->producto?->nombre ?? 'Producto' }}
                                                            @if($equipoReemplazoSeleccionado->producto?->modelo)
                                                                · {{ $equipoReemplazoSeleccionado->producto->modelo }}
                                                            @endif
                                                        @endif
                                                    </p>

                                                    <p
                                                        id="reemplazo_serial_{{ $caso->id }}"
                                                        class="mt-1 text-xs text-slate-500"
                                                    >
                                                        @if($equipoReemplazoSeleccionado?->serial_fabricante)
                                                            Serial fabricante: {{ $equipoReemplazoSeleccionado->serial_fabricante }}
                                                        @endif
                                                    </p>
                                                </div>

                                                <div class="text-left sm:text-right">
                                                    <p
                                                        id="reemplazo_precio_{{ $caso->id }}"
                                                        class="font-semibold text-slate-900"
                                                    >
                                                        @if($equipoReemplazoSeleccionado)
                                                            Bs {{ number_format((float) ($equipoReemplazoSeleccionado->precioVigente?->precio_publico ?? 0), 2, ',', '.') }}
                                                        @endif
                                                    </p>

                                                    <p
                                                        id="reemplazo_almacen_{{ $caso->id }}"
                                                        class="mt-1 text-xs text-slate-500"
                                                    >
                                                        {{ $equipoReemplazoSeleccionado?->almacenActual?->nombre }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                                <div class="rounded-xl bg-white p-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                        Valor original
                                                    </p>
                                                    <p class="mt-1 font-semibold text-slate-800">
                                                        Bs {{ number_format($precioOriginalCambio, 2, ',', '.') }}
                                                    </p>
                                                </div>

                                                <div class="rounded-xl bg-white p-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                        Valor reemplazo
                                                    </p>
                                                    <p
                                                        id="reemplazo_valor_preview_{{ $caso->id }}"
                                                        class="mt-1 font-semibold text-slate-800"
                                                    >
                                                        @if($equipoReemplazoSeleccionado)
                                                            Bs {{ number_format((float) ($equipoReemplazoSeleccionado->precioVigente?->precio_publico ?? 0), 2, ',', '.') }}
                                                        @endif
                                                    </p>
                                                </div>

                                                <div class="rounded-xl bg-white p-3">
                                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                                        Diferencia estimada
                                                    </p>
                                                    <p
                                                        id="reemplazo_diferencia_{{ $caso->id }}"
                                                        class="mt-1 font-semibold text-slate-800"
                                                    >
                                                        @if($equipoReemplazoSeleccionado)
                                                            @php
                                                                $diferenciaSeleccionada =
                                                                    (float) ($equipoReemplazoSeleccionado->precioVigente?->precio_publico ?? 0)
                                                                    - $precioOriginalCambio;
                                                            @endphp

                                                            @if($diferenciaSeleccionada > 0)
                                                                Cobro Bs {{ number_format($diferenciaSeleccionada, 2, ',', '.') }}
                                                            @elseif($diferenciaSeleccionada < 0)
                                                                A favor Bs {{ number_format(abs($diferenciaSeleccionada), 2, ',', '.') }}
                                                            @else
                                                                Sin diferencia
                                                            @endif
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        @if($equiposReemplazo->isNotEmpty())

                                            <div class="mt-3 flex flex-wrap items-center gap-3">
                                                <button
                                                    id="boton_elegir_reemplazo_{{ $caso->id }}"
                                                    type="button"
                                                    onclick="abrirSelectorReemplazo({{ $caso->id }})"
                                                    class="inline-flex items-center justify-center rounded-xl border border-amber-300 bg-white px-4 py-2.5 text-sm font-semibold text-amber-800 shadow-sm hover:bg-amber-50"
                                                >
                                                    {{ $equipoReemplazoSeleccionado ? 'Cambiar selección' : 'Elegir equipo' }}
                                                </button>

                                                <span class="text-xs text-slate-500">
                                                    Solo se muestran equipos actualmente disponibles para reemplazo.
                                                </span>
                                            </div>

                                            <dialog
                                                id="modal_reemplazo_{{ $caso->id }}"
                                                class="w-[min(94vw,72rem)] rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-900/40"
                                            >
                                                <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            <h3 class="text-lg font-semibold text-slate-900">
                                                                Seleccionar equipo de reemplazo
                                                            </h3>
                                                            <p class="mt-1 text-sm text-slate-500">
                                                                Busca por N° de equipo, serial, marca, modelo o producto.
                                                            </p>
                                                        </div>

                                                        <button
                                                            type="button"
                                                            onclick="cerrarSelectorReemplazo({{ $caso->id }})"
                                                            class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                                                        >
                                                            Cerrar
                                                        </button>
                                                    </div>

                                                    <div class="mt-4">
                                                        <input
                                                            id="buscar_reemplazo_{{ $caso->id }}"
                                                            type="search"
                                                            autocomplete="off"
                                                            oninput="filtrarSelectorReemplazo({{ $caso->id }}, this.value)"
                                                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                                            placeholder="Buscar equipo disponible..."
                                                        >
                                                    </div>
                                                </div>

                                                <div
                                                    id="lista_reemplazos_{{ $caso->id }}"
                                                    class="max-h-[65vh] space-y-3 overflow-y-auto p-5 sm:p-6"
                                                >
                                                    @foreach($equiposReemplazo as $equipoReemplazo)

                                                        @php
                                                            $precioReemplazoModal =
                                                                (float) (
                                                                    $equipoReemplazo->precioVigente?->precio_publico
                                                                    ?? 0
                                                                );

                                                            $textoBusquedaReemplazo = mb_strtolower(
                                                                implode(' ', [
                                                                    $equipoReemplazo->codigo_interno,
                                                                    $equipoReemplazo->serial_fabricante,
                                                                    $equipoReemplazo->producto?->nombre,
                                                                    $equipoReemplazo->producto?->modelo,
                                                                    $equipoReemplazo->producto?->marca?->nombre,
                                                                    $equipoReemplazo->almacenActual?->nombre,
                                                                ])
                                                            );
                                                        @endphp

                                                        <article
                                                            data-reemplazo-card
                                                            data-search="{{ $textoBusquedaReemplazo }}"
                                                            class="rounded-2xl border border-slate-200 bg-white p-4 hover:border-amber-300 hover:bg-amber-50/30"
                                                        >
                                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                                <div class="min-w-0">
                                                                    <div class="flex flex-wrap items-center gap-2">
                                                                        <span class="font-semibold text-slate-900">
                                                                            {{ $equipoReemplazo->codigo_interno }}
                                                                        </span>

                                                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                                                            Disponible
                                                                        </span>
                                                                    </div>

                                                                    <p class="mt-1 text-sm text-slate-700">
                                                                        {{ $equipoReemplazo->producto?->marca?->nombre }}
                                                                        {{ $equipoReemplazo->producto?->nombre ?? 'Producto' }}
                                                                        @if($equipoReemplazo->producto?->modelo)
                                                                            · {{ $equipoReemplazo->producto->modelo }}
                                                                        @endif
                                                                    </p>

                                                                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
                                                                        @if($equipoReemplazo->serial_fabricante)
                                                                            <span>
                                                                                Serial: {{ $equipoReemplazo->serial_fabricante }}
                                                                            </span>
                                                                        @endif

                                                                        <span>
                                                                            {{ $equipoReemplazo->almacenActual?->nombre ?? 'Sin almacén' }}
                                                                        </span>
                                                                    </div>
                                                                </div>

                                                                <div class="flex shrink-0 items-center gap-3 sm:text-right">
                                                                    <div>
                                                                        <p class="font-semibold text-slate-900">
                                                                            Bs {{ number_format($precioReemplazoModal, 2, ',', '.') }}
                                                                        </p>

                                                                        @php
                                                                            $diferenciaModal =
                                                                                $precioReemplazoModal
                                                                                - $precioOriginalCambio;
                                                                        @endphp

                                                                        <p class="mt-1 text-xs text-slate-500">
                                                                            @if($diferenciaModal > 0)
                                                                                Diferencia a cobrar:
                                                                                Bs {{ number_format($diferenciaModal, 2, ',', '.') }}
                                                                            @elseif($diferenciaModal < 0)
                                                                                Saldo a favor:
                                                                                Bs {{ number_format(abs($diferenciaModal), 2, ',', '.') }}
                                                                            @else
                                                                                Sin diferencia económica
                                                                            @endif
                                                                        </p>
                                                                    </div>

                                                                    <button
                                                                        type="button"
                                                                        data-caso-id="{{ $caso->id }}"
                                                                        data-equipo-id="{{ $equipoReemplazo->id }}"
                                                                        data-codigo="{{ $equipoReemplazo->codigo_interno }}"
                                                                        data-producto="{{ $equipoReemplazo->producto?->nombre ?? 'Producto' }}"
                                                                        data-modelo="{{ $equipoReemplazo->producto?->modelo ?? '' }}"
                                                                        data-serial="{{ $equipoReemplazo->serial_fabricante ?? '' }}"
                                                                        data-precio="{{ $precioReemplazoModal }}"
                                                                        data-almacen="{{ $equipoReemplazo->almacenActual?->nombre ?? 'Sin almacén' }}"
                                                                        data-precio-original="{{ $precioOriginalCambio }}"
                                                                        onclick="seleccionarEquipoReemplazo(this)"
                                                                        class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700"
                                                                    >
                                                                        Seleccionar
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </article>

                                                    @endforeach

                                                    <div
                                                        id="sin_resultados_reemplazo_{{ $caso->id }}"
                                                        hidden
                                                        class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500"
                                                    >
                                                        No se encontraron equipos con ese criterio.
                                                    </div>
                                                </div>
                                            </dialog>

                                        @else

                                            <div class="mt-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                                <p class="font-semibold">
                                                    No hay equipos disponibles para reemplazo.
                                                </p>
                                                <p class="mt-1">
                                                    Para autorizar el cambio debe existir al menos un equipo habilitado para venta y con precio vigente.
                                                </p>
                                                <a
                                                    href="{{ route('inventario.index') }}"
                                                    class="mt-3 inline-flex font-semibold text-amber-900 underline underline-offset-2"
                                                >
                                                    Ver inventario
                                                </a>
                                            </div>

                                        @endif

                                        @if(
                                            $errorCambioActual
                                            && $errors->has('equipo_entrante_id')
                                        )

                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $errors->first('equipo_entrante_id') }}
                                            </p>

                                        @endif

                                    </div>


                                    <div>

                                        <label
                                            for="motivo_cambio_{{ $caso->id }}"
                                            class="block text-sm font-semibold text-slate-700"
                                        >
                                            Motivo del cambio
                                        </label>

                                        <input
                                            id="motivo_cambio_{{ $caso->id }}"
                                            type="text"
                                            name="motivo"
                                            required
                                            minlength="5"
                                            maxlength="255"
                                            value="{{ $esFormularioCasoActual ? old('motivo') : '' }}"
                                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Ej.: Falla de hardware confirmada."
                                        >

                                        @if(
                                            $errorCambioActual
                                            && $errors->has('motivo')
                                        )

                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $errors->first('motivo') }}
                                            </p>

                                        @endif

                                    </div>


                                    <div>

                                        <label
                                            for="observacion_cambio_{{ $caso->id }}"
                                            class="block text-sm font-semibold text-slate-700"
                                        >
                                            Observación
                                        </label>

                                        <textarea
                                            id="observacion_cambio_{{ $caso->id }}"
                                            name="observacion"
                                            rows="3"
                                            maxlength="5000"
                                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="Información adicional del reemplazo."
                                        >{{ $esFormularioCasoActual ? old('observacion') : '' }}</textarea>

                                        @if(
                                            $errorCambioActual
                                            && $errors->has('observacion')
                                        )

                                            <p class="mt-2 text-sm text-red-600">
                                                {{ $errors->first('observacion') }}
                                            </p>

                                        @endif

                                    </div>


                                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                                        El equipo original quedará identificado en garantía.
                                        El reemplazo puede corresponder a otro modelo o producto,
                                        siempre que esté disponible y el cambio sea autorizado.
                                        El equipo entregado será descontado de su inventario
                                        disponible y quedará registrado como vendido.
                                    </div>


                                    @if($equiposReemplazo->isNotEmpty())

                                        <div class="flex justify-end">

                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700"
                                            >
                                                Confirmar cambio de equipo
                                            </button>

                                        </div>

                                    @endif

                                </form>

                            </details>

                        @endif


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
                                        {{ $estadoCasoTexto($estadoCaso) }}
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

                                                <select
                                                    id="tipo_intervencion_{{ $caso->id }}"
                                                    name="tipo_intervencion"
                                                    required
                                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    @php
                                                        $tipoIntervencionActual = $esFormularioCasoActual
                                                            ? old('tipo_intervencion')
                                                            : null;
                                                    @endphp

                                                    <option value="">Selecciona un tipo</option>
                                                    <option value="DIAGNOSTICO_COMPLEMENTARIO" @selected($tipoIntervencionActual === 'DIAGNOSTICO_COMPLEMENTARIO')>Diagnóstico complementario</option>
                                                    <option value="PRUEBA" @selected($tipoIntervencionActual === 'PRUEBA')>Prueba</option>
                                                    <option value="AJUSTE" @selected($tipoIntervencionActual === 'AJUSTE')>Ajuste</option>
                                                    <option value="REPARACION" @selected($tipoIntervencionActual === 'REPARACION')>Reparación</option>
                                                    <option value="MANTENIMIENTO" @selected($tipoIntervencionActual === 'MANTENIMIENTO')>Mantenimiento</option>
                                                    <option value="OTRO" @selected($tipoIntervencionActual === 'OTRO')>Otro</option>
                                                </select>

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

@once
<script>
    function abrirSelectorReemplazo(casoId) {
        const modal = document.getElementById(`modal_reemplazo_${casoId}`);
        const buscar = document.getElementById(`buscar_reemplazo_${casoId}`);

        if (!modal) {
            return;
        }

        modal.showModal();

        if (buscar) {
            buscar.value = '';
            filtrarSelectorReemplazo(casoId, '');
            setTimeout(() => buscar.focus(), 0);
        }
    }

    function cerrarSelectorReemplazo(casoId) {
        document.getElementById(`modal_reemplazo_${casoId}`)?.close();
    }

    function filtrarSelectorReemplazo(casoId, termino) {
        const lista = document.getElementById(`lista_reemplazos_${casoId}`);
        const sinResultados = document.getElementById(`sin_resultados_reemplazo_${casoId}`);

        if (!lista) {
            return;
        }

        const normalizar = (valor) =>
            String(valor ?? '')
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .trim();

        const criterio = normalizar(termino);
        let visibles = 0;

        lista.querySelectorAll('[data-reemplazo-card]').forEach((card) => {
            const coincide = normalizar(card.dataset.search).includes(criterio);
            card.hidden = !coincide;

            if (coincide) {
                visibles += 1;
            }
        });

        if (sinResultados) {
            sinResultados.hidden = visibles !== 0;
        }
    }

    function seleccionarEquipoReemplazo(boton) {
        const casoId = boton.dataset.casoId;
        const equipoId = boton.dataset.equipoId;
        const codigo = boton.dataset.codigo ?? '';
        const producto = boton.dataset.producto ?? '';
        const modelo = boton.dataset.modelo ?? '';
        const serial = boton.dataset.serial ?? '';
        const almacen = boton.dataset.almacen ?? '';
        const precio = Number(boton.dataset.precio ?? 0);
        const precioOriginal = Number(boton.dataset.precioOriginal ?? 0);

        const formato = new Intl.NumberFormat('es-BO', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const input = document.getElementById(`equipo_entrante_id_${casoId}`);
        const panel = document.getElementById(`reemplazo_seleccionado_${casoId}`);
        const botonElegir = document.getElementById(`boton_elegir_reemplazo_${casoId}`);

        if (input) {
            input.value = equipoId;
        }

        document.getElementById(`reemplazo_codigo_${casoId}`).textContent = codigo;
        document.getElementById(`reemplazo_producto_${casoId}`).textContent =
            modelo ? `${producto} · ${modelo}` : producto;
        document.getElementById(`reemplazo_serial_${casoId}`).textContent =
            serial ? `Serial fabricante: ${serial}` : 'Sin serial de fabricante registrado';
        document.getElementById(`reemplazo_precio_${casoId}`).textContent =
            `Bs ${formato.format(precio)}`;
        document.getElementById(`reemplazo_almacen_${casoId}`).textContent = almacen;
        document.getElementById(`reemplazo_valor_preview_${casoId}`).textContent =
            `Bs ${formato.format(precio)}`;

        const diferencia = precio - precioOriginal;
        let diferenciaTexto = 'Sin diferencia';

        if (diferencia > 0) {
            diferenciaTexto = `Cobro Bs ${formato.format(diferencia)}`;
        } else if (diferencia < 0) {
            diferenciaTexto = `A favor Bs ${formato.format(Math.abs(diferencia))}`;
        }

        document.getElementById(`reemplazo_diferencia_${casoId}`).textContent = diferenciaTexto;

        if (panel) {
            panel.hidden = false;
        }

        if (botonElegir) {
            botonElegir.textContent = 'Cambiar selección';
        }

        cerrarSelectorReemplazo(casoId);
    }
</script>
@endonce
