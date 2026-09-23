<x-layouts.oneshop title="Ventas | OneShop" page-title="Ventas">
<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">
                Ventas
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Consulta ventas, pagos, garantías y saldos pendientes.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row">
            <a
                href="{{ route('reservas.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Ver reservas activas
            </a>

            @if(auth()->user()->tienePermiso('ventas.crear'))
                <a
                    href="{{ route('ventas.create') }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    + Registrar venta
                </a>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    <x-ui.card padding="false">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-b bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-4">
                            Venta
                        </th>
                        <th class="px-5 py-4">
                            Cliente
                        </th>
                        <th class="px-5 py-4">
                            Equipos
                        </th>
                        <th class="px-5 py-4">
                            Total
                        </th>
                        <th class="px-5 py-4">
                            Estado
                        </th>
                        <th class="px-5 py-4">
                            <span class="sr-only">
                                Acción
                            </span>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($ventas as $venta)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-900">
                                    {{ $venta->numero }}
                                </p>

                                <p class="text-xs text-slate-500">
                                    {{ $venta->fecha_venta?->format('d/m/Y H:i') }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-800">
                                    {{ $venta->cliente?->nombre_completo ?? 'Sin cliente' }}
                                </p>

                                <p class="text-xs text-slate-500">
                                    {{ $venta->cliente?->telefono }}
                                </p>
                            </td>

                            <td class="px-5 py-4">
                                {{ $venta->detalles->count() }}
                            </td>

                            <td class="px-5 py-4 font-bold text-slate-900">
                                Bs {{ number_format((float) $venta->total, 2) }}
                            </td>

                            <td class="px-5 py-4">
                                <x-ui.badge
                                    :color="$venta->estado === 'ANULADA' ? 'red' : 'green'"
                                >
                                    {{ $venta->estado }}
                                </x-ui.badge>
                            </td>

                            <td class="px-5 py-4 text-right">
                                <a
                                    href="{{ route('ventas.show', $venta) }}"
                                    class="font-semibold text-oneshop-primary hover:underline"
                                >
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td
                                colspan="6"
                                class="px-5 py-12 text-center text-slate-500"
                            >
                                Todavía no existen ventas registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-5">
            {{ $ventas->links() }}
        </div>
    </x-ui.card>
</div>
</x-layouts.oneshop>
