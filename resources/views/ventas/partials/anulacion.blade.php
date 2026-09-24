@php
    $hayPagosComprometidos =
        (float) ($resumenPago['comprometido_total'] ?? 0) > 0;
@endphp

@if($venta->estado === 'ANULADA')
    <div class="rounded-2xl border border-red-200 bg-red-50 p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-bold uppercase tracking-wide text-red-700">
                    Venta anulada
                </p>

                <p class="mt-1 text-sm text-red-700/80">
                    Esta operación permanece en el historial y no puede recibir nuevos pagos.
                </p>
            </div>

            @if($venta->fecha_anulacion)
                <p class="text-xs font-semibold text-red-700">
                    {{ $venta->fecha_anulacion->format('d/m/Y H:i') }}
                </p>
            @endif
        </div>

        <div class="mt-4 rounded-xl bg-white/70 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                Motivo
            </p>

            <p class="mt-1 text-sm font-medium text-slate-900">
                {{ $venta->motivo_anulacion }}
            </p>

            @if($venta->anuladoPor)
                <p class="mt-2 text-xs text-slate-500">
                    Anulada por {{ $venta->anuladoPor->name }}.
                </p>
            @endif
        </div>
    </div>
@elseif(auth()->user()?->tienePermiso('ventas.anular'))
    <details
        class="group rounded-2xl border border-red-200 bg-white shadow-sm"
        @if($errors->has('anulacion') || $errors->has('motivo_anulacion')) open @endif
    >
        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
            <div>
                <p class="font-bold text-red-700">
                    Anular venta
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Solo procede si no existen pagos comprometidos ni casos de garantía registrados.
                </p>
            </div>

            <span class="text-sm font-semibold text-red-600 group-open:hidden">
                Abrir
            </span>

            <span class="hidden text-sm font-semibold text-slate-500 group-open:inline">
                Cerrar
            </span>
        </summary>

        <div class="border-t border-red-100 p-5">
            @error('anulacion')
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-700">
                    {{ $message }}
                </div>
            @enderror

            @if($hayPagosComprometidos)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-bold">
                        La anulación está bloqueada.
                    </p>

                    <p class="mt-1">
                        Esta venta tiene Bs {{ number_format((float) $resumenPago['comprometido_total'], 2) }}
                        en pagos pendientes de verificación o ya verificados.
                        Debe resolverse primero mediante el flujo de rechazo o devolución correspondiente.
                    </p>
                </div>
            @else
                <div class="mb-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                    La venta no se eliminará. Los equipos volverán a estar disponibles,
                    la garantía quedará anulada y se conservará todo el historial.
                </div>

                <form
                    method="POST"
                    action="{{ route('ventas.anular', $venta) }}"
                    onsubmit="return confirm('¿Confirmas la anulación de esta venta? Esta acción devolverá los equipos al inventario.');"
                >
                    @csrf

                    <label
                        for="motivo_anulacion"
                        class="block text-sm font-semibold text-slate-700"
                    >
                        Motivo de la anulación
                    </label>

                    <textarea
                        id="motivo_anulacion"
                        name="motivo_anulacion"
                        rows="3"
                        minlength="5"
                        maxlength="1000"
                        required
                        placeholder="Ej.: venta registrada por error antes de entregar el equipo."
                        class="mt-2 w-full rounded-xl border-slate-300 text-sm focus:border-red-500 focus:ring-red-500"
                    >{{ old('motivo_anulacion') }}</textarea>

                    @error('motivo_anulacion')
                        <p class="mt-2 text-sm font-medium text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="mt-4 flex justify-end">
                        <button
                            type="submit"
                            class="rounded-xl bg-red-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-red-700"
                        >
                            Confirmar anulación
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </details>
@endif
