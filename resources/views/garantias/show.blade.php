<x-layouts.oneshop
    :title="($casoEnfocado ? 'Caso ' . $casoEnfocado->numero : 'Garantía ' . $garantia->numero) . ' | OneShop'"
    :page-title="$casoEnfocado ? 'Caso de postventa' : 'Detalle de garantía'"
>

@php
    $venta = $garantia->detalleVenta?->venta;
    $detalle = $garantia->detalleVenta;

    $clienteNombre =
        $venta?->cliente_nombre_snapshot
        ?: $venta?->cliente?->nombre_completo
        ?: 'Cliente no disponible';

    $clienteTelefono =
        $venta?->cliente_telefono_snapshot
        ?: $venta?->cliente?->telefono;

    $garantiaVigente =
        $garantia->estado === 'VIGENTE'
        && $garantia->fecha_fin
        && $garantia->fecha_fin->isFuture();

    $estadoGarantiaTexto =
        $garantia->estado === 'ANULADA'
            ? 'Anulada'
            : ($garantiaVigente ? 'Vigente' : 'Vencida');

    $estadoGarantiaColor =
        $garantia->estado === 'ANULADA'
            ? 'red'
            : ($garantiaVigente ? 'green' : 'gray');

    $estadoCasoTexto = $casoEnfocado
        ? match (strtoupper((string) $casoEnfocado->estado)) {
            'ABIERTO' => 'Abierto',
            'DIAGNOSTICADO' => 'Diagnosticado',
            'EN_PROCESO' => 'En proceso',
            'CERRADO' => 'Cerrado',
            default => ucfirst(
                mb_strtolower(
                    str_replace(
                        '_',
                        ' ',
                        (string) $casoEnfocado->estado
                    )
                )
            ),
        }
        : null;

    $pasoCaso = $casoEnfocado
        ? match (strtoupper((string) $casoEnfocado->estado)) {
            'ABIERTO' => 1,
            'DIAGNOSTICADO' => 2,
            'EN_PROCESO' => 3,
            'CERRADO' => 4,
            default => 1,
        }
        : null;
@endphp


<div class="space-y-6">

    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

        <div>

            <a
                href="{{ route('garantias.index') }}"
                class="mb-2 inline-flex items-center gap-1 text-sm font-medium text-slate-500 hover:text-slate-800"
            >
                ← Volver a garantías
            </a>

            <div class="flex flex-wrap items-center gap-3">

                <h1 class="text-2xl font-bold text-slate-900">
                    @if($casoEnfocado)
                        Caso {{ $casoEnfocado->numero }}
                    @else
                        Garantía {{ $garantia->numero }}
                    @endif
                </h1>

                @if($casoEnfocado)

                    <x-ui.badge
                        :color="in_array($casoEnfocado->estado, ['ABIERTO', 'DIAGNOSTICADO', 'EN_PROCESO'], true) ? 'yellow' : 'green'"
                    >
                        {{ $estadoCasoTexto }}
                    </x-ui.badge>

                @else

                    <x-ui.badge :color="$estadoGarantiaColor">
                        {{ $estadoGarantiaTexto }}
                    </x-ui.badge>

                @endif

            </div>

            <p class="mt-2 text-sm text-slate-500">
                {{ $equipo->codigo_interno }}
                ·
                {{
                    trim(
                        ($equipo->producto?->marca?->nombre ?? '')
                        . ' '
                        . ($equipo->producto?->modelo ?? '')
                    )
                    ?: ($equipo->producto?->nombre ?? 'Equipo')
                }}
            </p>

        </div>


        <div class="flex flex-col gap-2 sm:flex-row">

            @if($casoEnfocado)

                <a
                    href="{{ route('garantias.show', $garantia) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Ver garantía completa
                </a>

            @endif

            <a
                href="{{ route('inventario.show', $equipo->codigo_interno) }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
            >
                Ver equipo
            </a>

            @if($venta)

                <a
                    href="{{ route('ventas.show', $venta) }}"
                    class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
                >
                    Ver venta
                </a>

            @endif

        </div>

    </div>


    @if(session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-800">
            {{ session('success') }}
        </div>

    @endif


    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Cliente
            </p>

            <p class="mt-2 font-bold text-slate-900">
                {{ $clienteNombre }}
            </p>

            @if($clienteTelefono)
                <p class="mt-1 text-sm text-slate-500">
                    {{ $clienteTelefono }}
                </p>
            @endif

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                N° equipo
            </p>

            <p class="mt-2 font-bold text-slate-900">
                {{ $equipo->codigo_interno }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
                Serial fabricante:
                {{ $equipo->serial_fabricante ?: 'No registrado' }}
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Vigencia
            </p>

            <p class="mt-2 font-bold text-slate-900">
                {{ $garantia->fecha_inicio?->format('d/m/Y') ?? '—' }}
                →
                {{ $garantia->fecha_fin?->format('d/m/Y') ?? '—' }}
            </p>

            <div class="mt-2">
                <x-ui.badge :color="$estadoGarantiaColor">
                    {{ $estadoGarantiaTexto }}
                </x-ui.badge>
            </div>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Venta de origen
            </p>

            <p class="mt-2 font-bold text-slate-900">
                {{ $venta?->numero ?? 'No disponible' }}
            </p>

            <p class="mt-1 text-sm text-slate-500">
                {{ $venta?->fecha_venta?->format('d/m/Y H:i') ?? 'Sin fecha' }}
            </p>

        </div>

    </div>


    @if($casoEnfocado)

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" data-case-progress>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900">Progreso del caso</h2>
                    <p class="mt-1 text-sm text-slate-500">Estado operativo del proceso de postventa.</p>
                </div>

                @if(strtoupper((string) $casoEnfocado->estado) === 'CERRADO')
                    <p class="text-sm font-medium text-emerald-700">
                        Cerrado {{ $casoEnfocado->fecha_cierre?->format('d/m/Y') ?? '' }}
                    </p>
                @endif
            </div>

            @php
                $pasosCaso = [
                    1 => 'Abierto',
                    2 => 'Diagnosticado',
                    3 => 'En proceso',
                    4 => 'Cerrado',
                ];
            @endphp

            <div class="mt-5 grid gap-3 sm:grid-cols-4">
                @foreach($pasosCaso as $numeroPaso => $textoPaso)
                    @php
                        $completado = $pasoCaso >= $numeroPaso;
                        $actual = $pasoCaso === $numeroPaso;
                    @endphp

                    <div @class([
                        'rounded-xl border p-3',
                        'border-emerald-200 bg-emerald-50' => $completado && !$actual,
                        'border-blue-300 bg-blue-50 ring-2 ring-blue-100' => $actual && $numeroPaso < 4,
                        'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-100' => $actual && $numeroPaso === 4,
                        'border-slate-200 bg-slate-50' => !$completado,
                    ])>
                        <div class="flex items-center gap-2">
                            <span @class([
                                'inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold',
                                'bg-emerald-600 text-white' => $completado && (!$actual || $numeroPaso === 4),
                                'bg-blue-600 text-white' => $actual && $numeroPaso < 4,
                                'bg-slate-200 text-slate-500' => !$completado,
                            ])>
                                {{ $completado && !$actual ? '✓' : $numeroPaso }}
                            </span>
                            <span class="text-sm font-semibold text-slate-800">{{ $textoPaso }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

    @endif


    @if($garantia->condiciones_snapshot || $garantia->exclusiones_snapshot)

        <details class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <summary class="cursor-pointer px-5 py-4 font-semibold text-slate-800">
                Condiciones de la garantía
            </summary>

            <div class="grid gap-5 border-t border-slate-100 p-5 md:grid-cols-2">

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Cobertura
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">
                        {{ $garantia->condiciones_snapshot ?: 'Sin condiciones registradas.' }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Exclusiones
                    </p>

                    <p class="mt-2 whitespace-pre-line text-sm text-slate-600">
                        {{ $garantia->exclusiones_snapshot ?: 'Sin exclusiones registradas.' }}
                    </p>
                </div>

            </div>

        </details>

    @endif


    <x-inventario.garantia
        :equipo="$equipo"
        :garantia="$garantia"
        :casoEnfocadoId="$casoEnfocado?->id"
        :equiposReemplazo="$equiposReemplazo"
        :metodos="$metodosPagoAjuste"
        :resumenes="$resumenesAjusteGarantia"
    />

</div>

</x-layouts.oneshop>
