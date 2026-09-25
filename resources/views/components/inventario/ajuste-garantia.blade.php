@props([
    'cambio',
    'resumen' => null,
    'metodos' => null,
])

@php
    $metodos =
        $metodos
        ?? collect();

    $puedeRegistrar =
        auth()->user()?->tienePermiso(
            'garantias.ajustes.registrar'
        );

    $puedeVerificar =
        auth()->user()?->tienePermiso(
            'garantias.ajustes.verificar'
        );

    $movimientos =
        $cambio->movimientosAjuste
            ?->sortByDesc('fecha_movimiento')
        ?? collect();

    $disponible =
        is_array($resumen)
        && ($resumen['disponible'] ?? false);

    $saldoDisponible =
        $disponible
            ? (float) ($resumen['saldo_disponible'] ?? 0)
            : 0;

    $saldo =
        $disponible
            ? (float) ($resumen['saldo'] ?? 0)
            : 0;

    $esCobro =
        $cambio->tipo_ajuste === 'COBRO_CLIENTE';

    $esDevolucion =
        $cambio->tipo_ajuste === 'SALDO_FAVOR_CLIENTE';

    $tituloAccion =
        $esDevolucion
            ? 'Registrar devolución'
            : 'Registrar cobro';

    $textoSaldo =
        $esDevolucion
            ? 'Pendiente por devolver'
            : 'Pendiente por cobrar';
@endphp

@if($disponible)

    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <p class="font-semibold text-slate-900">
                    Liquidación del ajuste
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Registro auditable de cobros o devoluciones asociados al cambio.
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
                    {{
                        $cambio->estado_ajuste === 'LIQUIDADO'
                            ? 'bg-emerald-100 text-emerald-800'
                            : 'bg-amber-100 text-amber-800'
                    }}
                "
            >
                {{ $cambio->estado_ajuste }}
            </span>

        </div>


        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Objetivo
                </p>

                <p class="mt-1 font-semibold text-slate-800">
                    Bs {{ number_format((float) ($resumen['objetivo'] ?? 0), 2, ',', '.') }}
                </p>
            </div>


            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Verificado
                </p>

                <p class="mt-1 font-semibold text-emerald-700">
                    Bs {{ number_format((float) ($resumen['verificado'] ?? 0), 2, ',', '.') }}
                </p>
            </div>


            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Pendiente de verificación
                </p>

                <p class="mt-1 font-semibold text-amber-700">
                    Bs {{ number_format((float) ($resumen['pendiente_verificacion'] ?? 0), 2, ',', '.') }}
                </p>
            </div>


            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    {{ $textoSaldo }}
                </p>

                <p class="mt-1 font-semibold text-slate-900">
                    Bs {{ number_format($saldo, 2, ',', '.') }}
                </p>

                @if($cambio->estado_ajuste === 'PENDIENTE')
                    <p class="mt-1 text-xs text-slate-500">
                        Disponible para registrar:
                        Bs {{ number_format($saldoDisponible, 2, ',', '.') }}
                    </p>
                @endif
            </div>

        </div>


        @if($errors->has('ajuste_garantia'))

            <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700">
                {{ $errors->first('ajuste_garantia') }}
            </div>

        @endif


        @if($errors->has('gestion_ajuste_garantia'))

            <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm font-medium text-red-700">
                {{ $errors->first('gestion_ajuste_garantia') }}
            </div>

        @endif


        @if(
            $puedeRegistrar
            && $cambio->estado_ajuste === 'PENDIENTE'
            && $saldoDisponible > 0
            && ($esCobro || $esDevolucion)
        )

            <details class="mt-5 rounded-xl border border-blue-200 bg-blue-50">

                <summary class="cursor-pointer px-4 py-3 font-semibold text-blue-900">
                    {{ $tituloAccion }}
                </summary>


                <form
                    method="POST"
                    action="{{ route('garantias.ajustes.store', $cambio) }}"
                    class="grid gap-4 border-t border-blue-200 bg-white p-4 lg:grid-cols-2"
                >
                    @csrf

                    <div>
                        <label
                            for="metodo_pago_id_{{ $cambio->id }}"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Método
                        </label>

                        <select
                            id="metodo_pago_id_{{ $cambio->id }}"
                            name="metodo_pago_id"
                            required
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >
                            <option value="">
                                Seleccione
                            </option>

                            @foreach($metodos as $metodo)

                                <option
                                    value="{{ $metodo->id }}"
                                    @selected(
                                        (int) old('metodo_pago_id')
                                        === (int) $metodo->id
                                    )
                                >
                                    {{ $metodo->nombre }}
                                    @if($metodo->requiere_verificacion)
                                        — requiere verificación
                                    @endif
                                </option>

                            @endforeach
                        </select>

                        @error('metodo_pago_id')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    <div>
                        <label
                            for="monto_ajuste_{{ $cambio->id }}"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Monto
                        </label>

                        <input
                            id="monto_ajuste_{{ $cambio->id }}"
                            name="monto"
                            type="number"
                            min="0.01"
                            max="{{ number_format($saldoDisponible, 2, '.', '') }}"
                            step="0.01"
                            value="{{ old(
                                'monto',
                                number_format($saldoDisponible, 2, '.', '')
                            ) }}"
                            required
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >

                        @error('monto')
                            <p class="mt-2 text-sm text-red-600">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    <div>
                        <label
                            for="referencia_ajuste_{{ $cambio->id }}"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Referencia
                        </label>

                        <input
                            id="referencia_ajuste_{{ $cambio->id }}"
                            name="referencia"
                            type="text"
                            maxlength="150"
                            value="{{ old('referencia') }}"
                            placeholder="QR, transferencia, recibo..."
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >
                    </div>


                    <div>
                        <label
                            for="comprobante_ajuste_{{ $cambio->id }}"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Comprobante / identificador
                        </label>

                        <input
                            id="comprobante_ajuste_{{ $cambio->id }}"
                            name="comprobante"
                            type="text"
                            maxlength="500"
                            value="{{ old('comprobante') }}"
                            placeholder="Ruta, código o referencia del comprobante"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >
                    </div>


                    <div class="lg:col-span-2">
                        <label
                            for="observacion_ajuste_{{ $cambio->id }}"
                            class="block text-sm font-semibold text-slate-700"
                        >
                            Observación
                        </label>

                        <textarea
                            id="observacion_ajuste_{{ $cambio->id }}"
                            name="observacion"
                            rows="2"
                            maxlength="3000"
                            class="mt-2 w-full rounded-xl border-slate-300 text-sm shadow-sm"
                        >{{ old('observacion') }}</textarea>
                    </div>


                    <div class="lg:col-span-2 flex justify-end">

                        <button
                            type="submit"
                            class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-800"
                        >
                            {{ $tituloAccion }}
                        </button>

                    </div>

                </form>

            </details>

        @endif


        <div class="mt-6">

            <div class="flex items-center justify-between">

                <div>
                    <p class="font-semibold text-slate-900">
                        Movimientos
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Historial de liquidación del ajuste económico.
                    </p>
                </div>

                <span class="text-xs font-semibold text-slate-400">
                    {{ $movimientos->count() }} registro(s)
                </span>

            </div>


            @if($movimientos->isEmpty())

                <div class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                    Todavía no existen movimientos económicos registrados.
                </div>

            @else

                <div class="mt-3 space-y-3">

                    @foreach($movimientos as $movimiento)

                        @php
                            $estadoClase = match ($movimiento->estado) {
                                'VERIFICADO' =>
                                    'bg-emerald-100 text-emerald-800',

                                'PENDIENTE' =>
                                    'bg-amber-100 text-amber-800',

                                'RECHAZADO' =>
                                    'bg-red-100 text-red-800',

                                default =>
                                    'bg-slate-100 text-slate-700',
                            };

                            $tipoTexto =
                                $movimiento->tipo_movimiento === 'DEVOLUCION'
                                    ? 'Devolución'
                                    : 'Cobro';
                        @endphp

                        <div class="rounded-xl border border-slate-200 bg-white p-4">

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                                <div>

                                    <div class="flex flex-wrap items-center gap-2">

                                        <p class="font-semibold text-slate-900">
                                            {{ $tipoTexto }}
                                            ·
                                            Bs {{ number_format((float) $movimiento->monto, 2, ',', '.') }}
                                        </p>

                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $estadoClase }}"
                                        >
                                            {{ $movimiento->estado }}
                                        </span>

                                    </div>


                                    <div class="mt-2 space-y-1 text-sm text-slate-500">

                                        <p>
                                            Método:
                                            <span class="font-medium text-slate-700">
                                                {{ $movimiento->metodoPago?->nombre ?? '—' }}
                                            </span>
                                        </p>

                                        <p>
                                            Registrado:
                                            <span class="font-medium text-slate-700">
                                                {{ $movimiento->registradoPor?->name ?? '—' }}
                                            </span>
                                            ·
                                            {{
                                                $movimiento->fecha_movimiento
                                                    ?->format('d/m/Y H:i')
                                                ?? '—'
                                            }}
                                        </p>

                                        @if($movimiento->referencia)
                                            <p>
                                                Referencia:
                                                <span class="font-medium text-slate-700">
                                                    {{ $movimiento->referencia }}
                                                </span>
                                            </p>
                                        @endif

                                        @if($movimiento->observacion)
                                            <p>
                                                Observación:
                                                <span class="font-medium text-slate-700">
                                                    {{ $movimiento->observacion }}
                                                </span>
                                            </p>
                                        @endif

                                        @if($movimiento->estado === 'RECHAZADO')
                                            <p class="text-red-700">
                                                Motivo:
                                                {{ $movimiento->motivo_rechazo ?? '—' }}
                                            </p>
                                        @endif

                                    </div>

                                </div>


                                @if(
                                    $movimiento->estado === 'PENDIENTE'
                                    && $puedeVerificar
                                )

                                    <div class="w-full space-y-2 sm:w-72">

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'garantias.ajustes.verificar',
                                                [
                                                    'cambio' => $cambio,
                                                    'movimiento' => $movimiento,
                                                ]
                                            ) }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="w-full rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800"
                                            >
                                                Verificar
                                            </button>
                                        </form>


                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'garantias.ajustes.rechazar',
                                                [
                                                    'cambio' => $cambio,
                                                    'movimiento' => $movimiento,
                                                ]
                                            ) }}"
                                            class="space-y-2"
                                        >
                                            @csrf

                                            <input
                                                name="motivo_rechazo"
                                                type="text"
                                                minlength="5"
                                                maxlength="255"
                                                required
                                                placeholder="Motivo del rechazo"
                                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm"
                                            >

                                            <button
                                                type="submit"
                                                class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100"
                                            >
                                                Rechazar
                                            </button>
                                        </form>

                                    </div>

                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </div>

    </div>

@endif
