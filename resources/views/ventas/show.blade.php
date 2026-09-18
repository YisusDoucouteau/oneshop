<x-layouts.oneshop title="Venta {{ $venta->numero }} | OneShop" page-title="Detalle de venta">
<div class="space-y-6" x-data="{ mostrarPago: {{ $errors->any() ? 'true' : 'false' }} }">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a href="{{ route('ventas.index') }}" class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800">← Volver a ventas</a>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">Venta {{ $venta->numero }}</h1>
                <x-ui.badge :color="$venta->estado === 'ANULADA' ? 'red' : 'green'">{{ $venta->estado }}</x-ui.badge>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                {{ $venta->fecha_venta?->format('d/m/Y H:i') }} · Vendedor: {{ $venta->vendedor?->name ?? '-' }}
            </p>
        </div>

        @if($venta->estado !== 'ANULADA' && (float) $resumenPago['saldo'] > 0)
            <button type="button" @click="mostrarPago = !mostrarPago" class="rounded-xl bg-oneshop-primary px-5 py-3 font-semibold text-white">
                Registrar pago
            </button>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-700">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700" role="alert">
            <p class="font-semibold">Revisa los datos del pago.</p>
            <ul class="mt-2 list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div x-show="mostrarPago" x-cloak class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
        <form method="POST" action="{{ route('ventas.pagos.store', $venta) }}" class="grid grid-cols-1 gap-4 lg:grid-cols-4 lg:items-end">
            @csrf
            <div>
                <label for="metodo_pago_id" class="block text-sm font-semibold text-slate-700">Método de pago</label>
                <select id="metodo_pago_id" name="metodo_pago_id" class="mt-2 w-full rounded-xl border-slate-300" required>
                    <option value="">Seleccionar</option>
                    @foreach($metodosPago as $metodo)
                        <option value="{{ $metodo->id }}" {{ (string) old('metodo_pago_id') === (string) $metodo->id ? 'selected' : '' }}>
                            {{ $metodo->nombre }}{{ $metodo->requiere_verificacion ? ' · requiere verificación' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="monto" class="block text-sm font-semibold text-slate-700">Monto</label>
                <input id="monto" name="monto" type="number" min="0.01" max="{{ $resumenPago['saldo'] }}" step="0.01" value="{{ old('monto', $resumenPago['saldo']) }}" class="mt-2 w-full rounded-xl border-slate-300" required>
            </div>
            <div>
                <label for="referencia" class="block text-sm font-semibold text-slate-700">Referencia</label>
                <input id="referencia" name="referencia" value="{{ old('referencia') }}" placeholder="QR o transferencia" class="mt-2 w-full rounded-xl border-slate-300">
            </div>
            <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 font-semibold text-white">Guardar pago</button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach([
            ['Total', $resumenPago['total'], 'slate'],
            ['Adelanto de reserva', $resumenPago['pagado_reserva'], 'blue'],
            ['Pagado después', $resumenPago['pagado_venta'], 'green'],
            ['Saldo pendiente', $resumenPago['saldo'], 'amber'],
        ] as [$titulo, $monto, $color])
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-500">{{ $titulo }}</p>
                <p class="mt-2 text-2xl font-bold text-slate-900">Bs {{ number_format((float) $monto, 2) }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-ui.card>
                <h2 class="mb-4 text-lg font-bold text-slate-900">Equipos vendidos</h2>
                <div class="space-y-3">
                    @foreach($venta->detalles as $detalle)
                        <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-bold text-slate-900">{{ $detalle->equipo?->codigo_interno ?? '-' }}</p>
                                <p class="text-sm text-slate-500">{{ $detalle->equipo?->producto?->nombre }} {{ $detalle->equipo?->producto?->modelo }}</p>
                                @if($detalle->garantia)
                                    <p class="mt-1 text-xs font-medium text-green-700">Garantía generada hasta {{ $detalle->garantia->fecha_fin?->format('d/m/Y') ?? '-' }}</p>
                                @endif
                            </div>
                            <p class="font-bold text-slate-900">Bs {{ number_format((float) $detalle->precio_unitario, 2) }}</p>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>

            <x-ui.card>
                <h2 class="mb-4 text-lg font-bold text-slate-900">Historial de pagos</h2>
                <div class="space-y-3">
                    @forelse($venta->pagos as $pago)
                        <div class="flex flex-col gap-2 rounded-xl border border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $pago->metodoPago?->nombre ?? 'Método no disponible' }}</p>
                                <p class="text-xs text-slate-500">{{ $pago->fecha_pago?->format('d/m/Y H:i') }}{{ $pago->referencia ? ' · '.$pago->referencia : '' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-slate-900">Bs {{ number_format((float) $pago->monto, 2) }}</p>
                                <p class="text-xs font-semibold {{ $pago->estado === 'VERIFICADO' ? 'text-green-700' : 'text-amber-700' }}">{{ $pago->estado }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Aún no se registraron pagos posteriores a la venta.</p>
                    @endforelse
                </div>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card>
                <h2 class="mb-4 text-lg font-bold text-slate-900">Cliente</h2>
                <p class="font-semibold text-slate-900">{{ $venta->cliente?->nombre_completo ?? 'Sin cliente' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $venta->cliente?->telefono ?? '-' }}</p>
                <p class="text-sm text-slate-500">{{ $venta->cliente?->correo ?? '-' }}</p>
            </x-ui.card>

            @if($venta->reserva)
                <x-ui.card>
                    <h2 class="mb-2 text-lg font-bold text-slate-900">Origen</h2>
                    <p class="text-sm text-slate-600">Esta venta proviene de la reserva <strong>{{ $venta->reserva->numero }}</strong>.</p>
                    <a href="{{ route('reservas.show', $venta->reserva) }}" class="mt-3 inline-block text-sm font-semibold text-oneshop-primary hover:underline">Ver reserva original</a>
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
</x-layouts.oneshop>
