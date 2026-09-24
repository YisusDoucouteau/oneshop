<x-layouts.oneshop

    title="Venta {{ $venta->numero }} | OneShop"

    page-title="Detalle de venta"

>

@php

    $hayErroresPago =

        $errors->has('pago')

        || $errors->has('metodo_pago_id')

        || $errors->has('monto')

        || $errors->has('referencia')

        || $errors->has('observacion');

@endphp

<div

    class="space-y-6"

   x-data="{ mostrarPago: {{ $hayErroresPago ? 'true' : 'false' }} }"

>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

        <div>

            <a

                href="{{ route('ventas.index') }}"

                class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800"

            >

                ← Volver a ventas

            </a>

            <div class="flex flex-wrap items-center gap-3">

                <h1 class="text-2xl font-bold text-slate-900">

                    Venta {{ $venta->numero }}

                </h1>

                <x-ui.badge

                    :color="$venta->estado === 'ANULADA' ? 'red' : 'green'"

                >

                    {{ $venta->estado }}

                </x-ui.badge>

            </div>

            <p class="mt-1 text-sm text-slate-500">

                {{ $venta->fecha_venta?->format('d/m/Y H:i') }}

                · Vendedor: {{ $venta->vendedor?->name ?? '-' }}

            </p>

        </div>

        <div class="flex flex-col gap-2 sm:flex-row">

            <a

                href="{{ route('ventas.boleta', $venta) }}"

                target="_blank"

                rel="noopener"

                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50"

            >

                Nota de venta y garantía

            </a>

            @if($venta->estado !== 'ANULADA' && (float) $resumenPago['saldo_disponible'] > 0)

                <button

                    type="button"

                    @click="mostrarPago = !mostrarPago"

                    class="rounded-xl bg-oneshop-primary px-5 py-3 font-semibold text-white"

                >

                    Registrar pago

                </button>

            @endif

        </div>

    </div>

    @if(session('success'))

        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-700">

            {{ session('success') }}

        </div>

    @endif

   @if($hayErroresPago)

    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700" role="alert">

        <p class="font-semibold">

            No se pudo registrar el pago.

        </p>

        <ul class="mt-2 list-disc pl-5 text-sm">

            @error('pago')

                <li>{{ $message }}</li>

            @enderror

            @error('metodo_pago_id')

                <li>{{ $message }}</li>

            @enderror

            @error('monto')

                <li>{{ $message }}</li>

            @enderror

            @error('referencia')

                <li>{{ $message }}</li>

            @enderror

            @error('observacion')

                <li>{{ $message }}</li>

            @enderror

        </ul>

    </div>

@endif

    <div

        x-show="mostrarPago"

        x-cloak

        class="rounded-2xl border border-blue-200 bg-blue-50 p-5"

    >

        <form

            method="POST"

            action="{{ route('ventas.pagos.store', $venta) }}"

            class="grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-end"

        >

            @csrf

            <div>

                <label for="metodo_pago_id" class="block text-sm font-semibold text-slate-700">

                    Método de pago

                </label>

                <select

                    id="metodo_pago_id"

                    name="metodo_pago_id"

                    class="mt-2 w-full rounded-xl border-slate-300"

                    required

                >

                    <option value="">Seleccionar</option>

                    @foreach($metodosPago as $metodo)

                        <option

                            value="{{ $metodo->id }}"

                            {{ (string) old('metodo_pago_id') === (string) $metodo->id ? 'selected' : '' }}

                        >

                            {{ $metodo->nombre }}

                            {{ $metodo->requiere_verificacion ? ' · requiere verificación' : '' }}

                        </option>

                    @endforeach

                </select>

            </div>

            <div>

                <label for="monto" class="block text-sm font-semibold text-slate-700">

                    Monto

                </label>

                <input

                    id="monto"

                    name="monto"

                    type="number"

                    min="0.01"

                    max="{{ $resumenPago['saldo_disponible'] }}"

                    step="0.01"

                    value="{{ old('monto', $resumenPago['saldo_disponible']) }}"

                    class="mt-2 w-full rounded-xl border-slate-300"

                    required

                >

            </div>

            <div>

                <label for="referencia" class="block text-sm font-semibold text-slate-700">

                    Referencia

                </label>

                <input

                    id="referencia"

                    name="referencia"

                    value="{{ old('referencia') }}"

                    placeholder="QR o transferencia"

                    class="mt-2 w-full rounded-xl border-slate-300"

                >

            </div>

            <button

                type="submit"

                class="rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white"

            >

                Guardar pago

            </button>

        </form>

    </div>

    <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1.2fr]">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-slate-500">

                Total de venta

            </p>

            <p class="mt-2 text-2xl font-black text-slate-900">

                Bs {{ number_format((float) $resumenPago['total'], 2) }}

            </p>

        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-sm text-slate-500">

                Saldo pendiente

            </p>

            <p class="mt-2 text-2xl font-black text-slate-900">

                Bs {{ number_format((float) $resumenPago['saldo'], 2) }}

            </p>
            @if((float) $resumenPago['pagado_total'] > 0)
                <p class="mt-1 text-xs text-slate-500">
                    Pagado verificado:
                    Bs {{ number_format((float) $resumenPago['pagado_total'], 2) }}
                </p>
            @endif

            @if((float) $resumenPago['pendiente_total'] > 0)
                <p class="mt-1 text-xs font-semibold text-amber-700">
                    En verificación:
                    Bs {{ number_format((float) $resumenPago['pendiente_total'], 2) }}
                </p>
            @endif

            @if((float) $resumenPago['saldo_disponible'] !== (float) $resumenPago['saldo'])
                <p class="mt-1 text-xs text-slate-500">
                    Disponible para nuevos pagos:
                    Bs {{ number_format((float) $resumenPago['saldo_disponible'], 2) }}
                </p>
            @endif

        </div>

        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">

            <p class="text-sm font-bold uppercase tracking-wide text-emerald-700">

                Ganancia de la venta

            </p>

            @if($economiaCompleta)

                <p class="mt-2 text-3xl font-black text-emerald-700">

                    Bs {{ number_format((float) $gananciaVenta, 2) }}

                </p>

                <p class="mt-1 text-xs text-emerald-700/80">

                    Valor histórico congelado al registrar la venta.

                </p>

            @else

                <p class="mt-2 text-sm font-semibold text-slate-600">

                    Información económica histórica no disponible para esta venta.

                </p>

            @endif

        </div>

    </div>

    @if($economiaCompleta && $puedeVerDetalleEconomico && $resumenEconomicoAdmin)

        <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm">

            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">

                <div>

                    <p class="font-bold text-slate-900">

                        Detalle económico

                    </p>

                    <p class="mt-1 text-sm text-slate-500">

                        Costos y reparto congelados al momento de la venta.

                    </p>

                </div>

                <span class="text-sm font-semibold text-slate-500 group-open:hidden">

                    Ver detalle

                </span>

                <span class="hidden text-sm font-semibold text-slate-500 group-open:inline">

                    Ocultar

                </span>

            </summary>

            <div class="border-t border-slate-100 p-5">

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">

                    <div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                            Costo al vender

                        </p>

                        <p class="mt-1 font-bold text-slate-900">

                            Bs {{ number_format((float) $resumenEconomicoAdmin['costo_total'], 2) }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                            Margen total

                        </p>

                        <p class="mt-1 font-bold text-slate-900">

                            Bs {{ number_format((float) $resumenEconomicoAdmin['margen_total'], 2) }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                            Hugo

                        </p>

                        <p class="mt-1 font-bold text-slate-900">

                            Bs {{ number_format((float) $resumenEconomicoAdmin['hugo'], 2) }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                            Daniel

                        </p>

                        <p class="mt-1 font-bold text-slate-900">

                            Bs {{ number_format((float) $resumenEconomicoAdmin['daniel'], 2) }}

                        </p>

                    </div>

                    <div>

                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                            Tienda

                        </p>

                        <p class="mt-1 font-bold text-slate-900">

                            Bs {{ number_format((float) $resumenEconomicoAdmin['tienda'], 2) }}

                        </p>

                    </div>

                </div>

            </div>

        </details>

    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="space-y-6 xl:col-span-2">

            <x-ui.card>

                <div class="mb-4">

                    <h2 class="text-lg font-bold text-slate-900">

                        Equipos vendidos

                    </h2>

                    <p class="mt-1 text-sm text-slate-500">

                        Precios y economía corresponden al momento exacto de la venta.

                    </p>

                </div>

                <div class="space-y-4">

                    @foreach($venta->detalles as $detalle)

                        @php

                            $tieneEconomia =

                                $detalle->ganancia_snapshot !== null

                                &&

                                $detalle->margen_total_snapshot !== null;

                        @endphp

                        <article class="rounded-2xl border border-slate-200 p-5">

                           <div class="grid gap-4 xl:grid-cols-[minmax(18rem,1fr)_minmax(32rem,1.5fr)] xl:items-start">

                                <div class="min-w-0">

                                    <p class="font-bold text-slate-900">

                                        {{ $detalle->codigo_interno_snapshot ?? $detalle->equipo?->codigo_interno ?? $detalle->producto?->codigo ?? '-' }}

                                    </p>

                                    <p class="mt-1 text-sm text-slate-500">

                                        {{ $detalle->marca_snapshot ?? $detalle->equipo?->producto?->marca?->nombre ?? '' }}

                                        {{ $detalle->modelo_snapshot ?? $detalle->equipo?->producto?->modelo ?? $detalle->producto?->modelo }} ·

                                        {{ $detalle->condicion_venta_snapshot ?? 'Condición no congelada' }}

                                    </p>

                                    @if($detalle->garantia)

                                        <p class="mt-2 text-xs font-medium text-green-700">

                                            Garantía generada hasta

                                            {{ $detalle->garantia->fecha_fin?->format('d/m/Y') ?? '-' }}

                                        </p>

                                    @endif

                                </div>

                               <div class="grid w-full gap-3 sm:grid-cols-3">

                                    <div class="rounded-xl bg-slate-50 p-3">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                                            Publicado

                                        </p>

                                        <p class="mt-1 font-bold text-slate-800">

                                            Bs {{ number_format((float) $detalle->precio_lista_snapshot, 2) }}

                                        </p>

                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-3">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">

                                            Descuento

                                        </p>

                                        <p class="mt-1 font-bold text-slate-800">

                                            Bs {{ number_format((float) $detalle->descuento_unitario, 2) }}

                                        </p>

                                    </div>

                                    <div class="rounded-xl bg-slate-950 p-3 text-white">

                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-300">

                                            Vendido

                                        </p>

                                        <p class="mt-1 text-lg font-black">

                                            Bs {{ number_format((float) $detalle->precio_unitario, 2) }}

                                        </p>

                                    </div>

                                </div>

                            </div>

                            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">

                                <div>

                                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">

                                        Ganancia

                                    </p>

                                    @if($tieneEconomia)

                                        <p class="mt-1 text-2xl font-black text-emerald-600">

                                            Bs {{ number_format((float) $detalle->ganancia_snapshot, 2) }}

                                        </p>

                                    @else

                                        <p class="mt-1 text-sm font-semibold text-slate-500">

                                            No disponible

                                        </p>

                                    @endif

                                </div>

                                @if($tieneEconomia && $puedeVerDetalleEconomico)

                                    <details class="w-full sm:w-auto sm:min-w-[19rem]">

                                        <summary class="cursor-pointer rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">

                                            Ver costo y TC

                                        </summary>

                                        <div class="mt-2 space-y-2 rounded-xl bg-slate-50 p-4 text-sm">

                                            <div class="flex justify-between gap-4">

                                                <span class="text-slate-500">

                                                    Costo al vender

                                                </span>

                                                <strong class="text-slate-900">

                                                    Bs {{ number_format((float) $detalle->costo_unitario_snapshot, 2) }}

                                                </strong>

                                            </div>

                                            <div class="flex justify-between gap-4">

                                                <span class="text-slate-500">

                                                    Margen total

                                                </span>

                                                <strong class="text-slate-900">

                                                    Bs {{ number_format((float) $detalle->margen_total_snapshot, 2) }}

                                                </strong>

                                            </div>

                                            <div class="flex justify-between gap-4">

                                                <span class="text-slate-500">

                                                    TC utilizado

                                                </span>

                                                <strong class="text-slate-900">

                                                    {{ $detalle->tipo_cambio_valor_snapshot !== null

                                                        ? number_format((float) $detalle->tipo_cambio_valor_snapshot, 6, '.', '')

                                                        : 'No aplica' }}

                                                </strong>

                                            </div>

                                            <div class="flex justify-between gap-4">

                                                <span class="text-slate-500">

                                                    Moneda origen

                                                </span>

                                                <strong class="text-slate-900">

                                                    {{ $detalle->moneda_origen_snapshot ?? 'BOB / histórico' }}

                                                </strong>

                                            </div>

                                            @if($detalle->monto_origen_snapshot !== null)

                                                <div class="flex justify-between gap-4">

                                                    <span class="text-slate-500">

                                                        Monto origen

                                                    </span>

                                                    <strong class="text-slate-900">

                                                        {{ number_format((float) $detalle->monto_origen_snapshot, 2) }}

                                                        {{ $detalle->moneda_origen_snapshot }}

                                                    </strong>

                                                </div>

                                            @endif

                                            @if($detalle->fuente_costo_snapshot)

                                                <div class="flex justify-between gap-4">

                                                    <span class="text-slate-500">

                                                        Fuente

                                                    </span>

                                                    <strong class="text-right text-slate-900">

                                                        {{ $detalle->fuente_costo_snapshot }}

                                                    </strong>

                                                </div>

                                            @endif

                                        </div>

                                    </details>

                                @endif

                            </div>

                        </article>

                    @endforeach

                </div>

            </x-ui.card>

            <x-ui.card>

                <h2 class="mb-4 text-lg font-bold text-slate-900">

                    Historial de pagos

                </h2>

                @error('gestion_pago')
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
        {{ $message }}
    </div>
@enderror

@error('motivo_rechazo')
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-700">
        {{ $message }}
    </div>
@enderror

<div class="space-y-3">
    @forelse($venta->pagos as $pago)
        <div class="rounded-xl border border-slate-200 p-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="font-semibold text-slate-900">
                        {{ $pago->metodoPago?->nombre ?? 'Método no disponible' }}
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        {{ $pago->fecha_pago?->format('d/m/Y H:i') }}

                        @if($pago->referencia)
                            · {{ $pago->referencia }}
                        @endif
                    </p>

                    @if($pago->estado === 'VERIFICADO' && $pago->fecha_verificacion)
                        <p class="mt-1 text-xs text-green-700">
                            Verificado el
                            {{ $pago->fecha_verificacion->format('d/m/Y H:i') }}

                            @if($pago->verificadoPor)
                                por {{ $pago->verificadoPor->name }}
                            @endif
                        </p>
                    @endif

                    @if($pago->estado === 'RECHAZADO')
                        <p class="mt-1 text-xs text-red-700">
                            Rechazado
                            @if($pago->motivo_rechazo)
                                · {{ $pago->motivo_rechazo }}
                            @endif
                        </p>
                    @endif
                </div>

                <div class="text-right">
                    <p class="font-bold text-slate-900">
                        Bs {{ number_format((float) $pago->monto, 2) }}
                    </p>

                    <p
                        class="text-xs font-semibold
                            {{ $pago->estado === 'VERIFICADO'
                                ? 'text-green-700'
                                : ($pago->estado === 'RECHAZADO'
                                    ? 'text-red-700'
                                    : 'text-amber-700') }}"
                    >
                        {{ $pago->estado }}
                    </p>
                </div>
            </div>

            @if(
                $pago->estado === 'PENDIENTE'
                && auth()->user()?->tienePermiso('pagos.verificar')
            )
                <div class="mt-4 border-t border-slate-100 pt-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-end">
                        <form
                            method="POST"
                            action="{{ route('ventas.pagos.verificar', [$venta, $pago]) }}"
                            onsubmit="return confirm('¿Confirmas que este pago fue recibido correctamente?');"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="w-full rounded-xl bg-green-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-green-700 sm:w-auto"
                            >
                                Verificar pago
                            </button>
                        </form>

                        <details class="group sm:min-w-[22rem]">
                            <summary class="cursor-pointer list-none rounded-xl border border-red-200 px-4 py-2.5 text-center text-sm font-bold text-red-700 hover:bg-red-50">
                                Rechazar pago
                            </summary>

                            <form
                                method="POST"
                                action="{{ route('ventas.pagos.rechazar', [$venta, $pago]) }}"
                                class="mt-3 rounded-xl border border-red-100 bg-red-50 p-4"
                                onsubmit="return confirm('¿Confirmas el rechazo de este pago?');"
                            >
                                @csrf

                                <label
                                    for="motivo_rechazo_{{ $pago->id }}"
                                    class="block text-sm font-semibold text-slate-700"
                                >
                                    Motivo del rechazo
                                </label>

                                <textarea
                                    id="motivo_rechazo_{{ $pago->id }}"
                                    name="motivo_rechazo"
                                    rows="2"
                                    minlength="5"
                                    maxlength="1000"
                                    required
                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm"
                                    placeholder="Ej.: transferencia no localizada o comprobante inválido."
                                ></textarea>

                                <div class="mt-3 flex justify-end">
                                    <button
                                        type="submit"
                                        class="rounded-xl bg-red-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-red-700"
                                    >
                                        Confirmar rechazo
                                    </button>
                                </div>
                            </form>
                        </details>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">
            Aún no se registraron pagos posteriores a la venta.
        </p>
    @endforelse
</div>

            </x-ui.card>

        </div>

        <div class="space-y-6">

            <x-ui.card>

                <h2 class="mb-4 text-lg font-bold text-slate-900">

                    Cliente

                </h2>

                <p class="font-semibold text-slate-900">

                    {{ $venta->cliente_nombre_snapshot ?? $venta->cliente?->nombre_completo ?? 'Sin cliente' }}

                </p>

                <p class="mt-1 text-sm text-slate-500">

                    {{ $venta->cliente_telefono_snapshot ?? $venta->cliente?->telefono ?? '-' }}

                </p>

                <p class="text-sm text-slate-500">

                    {{ $venta->cliente?->correo ?? '-' }}

                </p>

            </x-ui.card>

            @if($venta->reserva)

                <x-ui.card>

                    <h2 class="mb-2 text-lg font-bold text-slate-900">

                        Origen

                    </h2>

                    <p class="text-sm text-slate-600">

                        Esta venta proviene de la reserva

                        <strong>{{ $venta->reserva->numero }}</strong>.

                    </p>

                    <a

                        href="{{ route('reservas.show', $venta->reserva) }}"

                        class="mt-3 inline-block text-sm font-semibold text-oneshop-primary hover:underline"

                    >

                        Ver reserva original

                    </a>

                </x-ui.card>

            @endif

            @if((float) $resumenPago['pagado_reserva'] > 0)

                <x-ui.card>

                    <h2 class="mb-2 text-lg font-bold text-slate-900">

                        Adelanto aplicado

                    </h2>

                    <p class="text-2xl font-black text-slate-900">

                        Bs {{ number_format((float) $resumenPago['pagado_reserva'], 2) }}

                    </p>

                    <p class="mt-1 text-sm text-slate-500">

                        Proviene de la reserva original y no se duplicó al convertirla en venta.

                    </p>

                </x-ui.card>

            @endif

            @include('ventas.partials.anulacion', ['venta' => $venta])

        </div>

    </div>

</div>

</x-layouts.oneshop>