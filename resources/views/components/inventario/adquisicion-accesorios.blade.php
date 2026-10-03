@props([
    'equipo',
])

@php
    $incorporacion = $equipo->incorporacionUnidad;
    $unidad = $incorporacion?->unidadAdquirida;
    $detalleEnvio = $unidad?->envioImportacionUnidad;

    $ultimoCosto =
        $unidad?->historialCostos
            ?->sortByDesc('fecha_calculo')
            ->first();

    $puedeVerFinanzas =
        auth()->user()?->tienePermiso('finanzas.ver')
        ?? false;

    $cargadorOrigen = $unidad?->tiene_cargador;

    $incluyeCargador =
        $detalleEnvio?->incluye_cargador;

    $cargadorRecibido =
        $detalleEnvio?->cargador_recibido;

    $estadoRecepcionCargador =
        !$detalleEnvio
            ? 'No aplica'
            : (
                $incluyeCargador === false
                    ? 'No correspondía'
                    : (
                        $cargadorRecibido === true
                            ? 'Recibido'
                            : (
                                $cargadorRecibido === false
                                    ? 'No recibido'
                                    : 'Sin verificar'
                            )
                    )
            );

    $claseRecepcionCargador =
        match ($estadoRecepcionCargador) {
            'Recibido' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'No recibido' => 'border-red-200 bg-red-50 text-red-800',
            default => 'border-slate-200 bg-slate-50 text-slate-700',
        };

    $monedaCompra =
        $unidad?->moneda?->codigo
        ?? '—';

    $tipoCambioCompra =
        $unidad?->tipoCambioCompra?->valor;

    $precioCompra =
        $unidad?->precio_compra;

    $precioCompraBob =
        $unidad?->precio_compra_bob;
@endphp

@if($unidad)

<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 px-6 py-5">
        <h2 class="font-semibold text-slate-950">
            Adquisición y accesorios asociados
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Datos históricos que permanecen ligados al equipo después de su incorporación.
        </p>
    </div>

    <div class="p-6">

        <div class="mb-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Cargador asociado
            </p>

            <p class="mt-1 text-sm text-slate-500">
                Se conserva la relación registrada en origen, durante el traslado y en la recepción de Oruro.
            </p>
        </div>

        <div class="grid gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Registrado en origen
                </p>

                <p class="mt-2 font-semibold text-slate-900">
                    {{
                        $cargadorOrigen === null
                            ? 'No registrado'
                            : ($cargadorOrigen ? 'Sí' : 'No')
                    }}
                </p>
            </div>

            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                    Incluido en el envío
                </p>

                <p class="mt-2 font-semibold text-slate-900">
                    {{
                        $incluyeCargador === null
                            ? 'No aplica'
                            : ($incluyeCargador ? 'Sí' : 'No')
                    }}
                </p>
            </div>

            <div class="rounded-xl border p-4 {{ $claseRecepcionCargador }}">
                <p class="text-xs font-semibold uppercase tracking-wide opacity-70">
                    Recepción en Oruro
                </p>

                <p class="mt-2 font-semibold">
                    {{ $estadoRecepcionCargador }}
                </p>
            </div>

        </div>

        <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="font-semibold text-blue-900">
                Estado inicial de inventario:
                {{ $equipo->estadoActual?->nombre ?? 'No registrado' }}
            </p>

            <p class="mt-1 text-sm text-blue-700">
                La recepción física y la incorporación formal permanecen como eventos distintos dentro de la trazabilidad.
            </p>
        </div>


        @if($puedeVerFinanzas)

            <div class="mt-6 border-t border-slate-200 pt-6">

                <div class="mb-5">
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Costo de adquisición
                    </p>

                    <p class="mt-1 text-sm text-slate-500">
                        Valores históricos de compra. No utilizan el tipo de cambio comercial vigente de venta.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Compra original
                        </p>

                        <p class="mt-2 font-semibold text-slate-900">
                            @if($precioCompra !== null)
                                {{ number_format((float) $precioCompra, 2, '.', ',') }}
                                {{ $monedaCompra }}
                            @else
                                No registrado
                            @endif
                        </p>

                        @if($unidad->fecha_compra)
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $unidad->fecha_compra->format('d/m/Y') }}
                            </p>
                        @endif
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Tipo de cambio histórico
                        </p>

                        <p class="mt-2 font-semibold text-slate-900">
                            @if($tipoCambioCompra !== null)
                                {{ number_format((float) $tipoCambioCompra, 2, '.', '') }}
                                BOB/{{ $monedaCompra }}
                            @else
                                No registrado
                            @endif
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            Equivalente de compra
                        </p>

                        <p class="mt-2 font-semibold text-slate-900">
                            @if($precioCompraBob !== null)
                                Bs {{ number_format((float) $precioCompraBob, 2, '.', ',') }}
                            @else
                                No registrado
                            @endif
                        </p>
                    </div>

                    <div class="rounded-xl border border-oneshop-primary bg-oneshop-light p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-oneshop-dark/70">
                            Costo real incorporado
                        </p>

                        <p class="mt-2 font-semibold text-oneshop-dark">
                            @if($ultimoCosto)
                                Bs {{ number_format((float) $ultimoCosto->costo_total, 2, '.', ',') }}
                            @else
                                No calculado
                            @endif
                        </p>

                        @if($ultimoCosto)
                            <p class="mt-1 text-xs text-oneshop-dark/70">
                                Compra + lote + intervenciones
                            </p>
                        @endif
                    </div>

                </div>

                @if($ultimoCosto)
                    <div class="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <span class="text-slate-500">Compra:</span>
                            <strong class="ml-1 text-slate-800">
                                Bs {{ number_format((float) $ultimoCosto->costo_compra, 2, '.', ',') }}
                            </strong>
                        </div>

                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <span class="text-slate-500">Costos de lote:</span>
                            <strong class="ml-1 text-slate-800">
                                Bs {{ number_format((float) $ultimoCosto->costos_lote, 2, '.', ',') }}
                            </strong>
                        </div>

                        <div class="rounded-xl bg-slate-50 px-4 py-3">
                            <span class="text-slate-500">Intervenciones:</span>
                            <strong class="ml-1 text-slate-800">
                                Bs {{ number_format((float) $ultimoCosto->intervenciones, 2, '.', ',') }}
                            </strong>
                        </div>
                    </div>
                @endif

            </div>

        @endif

    </div>

</section>

@endif
