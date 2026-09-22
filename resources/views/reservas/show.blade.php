<x-layouts.oneshop
    title="Detalle reserva | OneShop"
    page-title="Detalle de reserva"
>
<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <a
                href="{{ route('reservas.index') }}"
                class="text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Volver a reservas
            </a>

            <h1 class="mt-2 text-2xl font-bold text-slate-900">
                Reserva {{ $reserva->numero }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $reserva->cliente?->nombre_completo ?? 'Cliente sin nombre' }}
                @if($reserva->cliente?->telefono)
                    · {{ $reserva->cliente->telefono }}
                @endif
            </p>
        </div>

        @if($reserva->estado === 'ACTIVA')
            <div class="flex flex-wrap gap-2">
                <form
                    method="POST"
                    action="{{ route('reservas.cancelar', $reserva) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50"
                    >
                        Liberar reserva
                    </button>
                </form>

                <form
                    method="POST"
                    action="{{ route('reservas.convertirVenta', $reserva) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="rounded-xl bg-oneshop-primary px-4 py-2.5 text-sm font-semibold text-white"
                    >
                        Convertir en venta
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_20rem]">
        <x-ui.card>
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900">
                        Equipos reservados
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Los valores mostrados son los acordados al crear la reserva.
                    </p>
                </div>

                <span class="text-sm font-semibold text-slate-500">
                    {{ $reserva->detalles->count() }}
                    {{ $reserva->detalles->count() === 1 ? 'equipo' : 'equipos' }}
                </span>
            </div>

            <div class="mt-5 space-y-3">
                @foreach($reserva->detalles as $detalle)
                    @php
                        $precioPublicadoReserva =
                            (float) $detalle->precio_acordado
                            + (float) $detalle->descuento_acordado;
                    @endphp

                    <div class="rounded-xl border border-slate-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="font-bold text-slate-900">
                                    {{ $detalle->equipo?->codigo_interno ?? '-' }}
                                </p>

                                <p class="mt-1 text-sm text-slate-500">
                                    {{ $detalle->equipo?->producto?->nombre ?? '' }}
                                    {{ $detalle->equipo?->producto?->modelo ?? '' }}
                                </p>
                            </div>

                            <div class="sm:text-right">
                                @if((float) $detalle->descuento_acordado > 0)
                                    <p class="text-xs text-slate-400 line-through">
                                        Bs {{ number_format($precioPublicadoReserva, 2) }}
                                    </p>
                                @endif

                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                    Precio acordado
                                </p>

                                <p class="mt-1 text-xl font-black text-slate-900">
                                    Bs {{ number_format((float) $detalle->precio_acordado, 2) }}
                                </p>

                                @if((float) $detalle->descuento_acordado > 0)
                                    <p class="mt-1 text-xs font-medium text-emerald-700">
                                        Rebaja:
                                        Bs {{ number_format((float) $detalle->descuento_acordado, 2) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="space-y-6">
            <x-ui.card>
                <h2 class="font-bold text-slate-900">
                    Estado
                </h2>

                <div class="mt-4">
                    @if($reserva->estado === 'ACTIVA')
                        <x-ui.badge color="yellow">
                            Activa
                        </x-ui.badge>
                    @elseif($reserva->estado === 'CONVERTIDA')
                        <x-ui.badge color="green">
                            Convertida
                        </x-ui.badge>
                    @elseif($reserva->estado === 'VENCIDA')
                        <x-ui.badge color="gray">
                            Vencida
                        </x-ui.badge>
                    @elseif($reserva->estado === 'LIBERADA')
                        <x-ui.badge color="gray">
                            Liberada
                        </x-ui.badge>
                    @else
                        <x-ui.badge color="gray">
                            {{ $reserva->estado }}
                        </x-ui.badge>
                    @endif
                </div>

                <dl class="mt-5 space-y-4 text-sm">
                    <div>
                        <dt class="text-slate-400">
                            Registrada
                        </dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $reserva->fecha_reserva?->format('d/m/Y H:i') ?? '-' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-slate-400">
                            Vence
                        </dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $reserva->fecha_expiracion?->format('d/m/Y H:i') ?? '-' }}
                        </dd>
                    </div>

                    @if($reserva->fecha_cierre)
                        <div>
                            <dt class="text-slate-400">
                                Cerrada
                            </dt>
                            <dd class="mt-1 font-medium text-slate-900">
                                {{ $reserva->fecha_cierre->format('d/m/Y H:i') }}
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-slate-400">
                            Registrado por
                        </dt>
                        <dd class="mt-1 font-medium text-slate-900">
                            {{ $reserva->registradoPor?->name ?? '-' }}
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            @if($reserva->observacion)
                <x-ui.card>
                    <h2 class="font-bold text-slate-900">
                        Observación
                    </h2>

                    <p class="mt-3 text-sm text-slate-600">
                        {{ $reserva->observacion }}
                    </p>
                </x-ui.card>
            @endif
        </div>
    </div>
</div>
</x-layouts.oneshop>
