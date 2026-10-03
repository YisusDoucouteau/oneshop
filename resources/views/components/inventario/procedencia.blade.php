@props([
    'equipo',
])

@php
    $incorporacion = $equipo->incorporacionUnidad;
    $unidad = $incorporacion?->unidadAdquirida;
    $detalleEnvio = $unidad?->envioImportacionUnidad;
    $envio = $detalleEnvio?->envioImportacion;
    $lote = $equipo->detalleLote?->lote;

    $fechaIngresoInventario =
        $incorporacion?->fecha_incorporacion
        ?? $equipo->fecha_registro;
@endphp

<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 px-6 py-5">
        <h2 class="font-semibold text-slate-950">
            Procedencia y trazabilidad de origen
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Relación entre la compra, la unidad adquirida, el traslado y su incorporación formal.
        </p>
    </div>

    <div class="grid gap-5 p-6 sm:grid-cols-2 xl:grid-cols-4">

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Código de lote
            </p>

            <p class="mt-2 font-medium text-slate-900">
                {{ $lote?->codigo ?? '—' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Proveedor
            </p>

            <p class="mt-2 font-medium text-slate-900">
                {{ $lote?->proveedor?->nombre ?? '—' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Origen
            </p>

            <p class="mt-2 font-medium text-slate-900">
                {{ $lote?->origen ?? '—' }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Cantidad de la línea
            </p>

            <p class="mt-2 font-medium text-slate-900">
                {{
                    $equipo->detalleLote?->cantidad_esperada !== null
                        ? $equipo->detalleLote->cantidad_esperada . ' unidades'
                        : '—'
                }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Código de trazabilidad
            </p>

            @if($unidad)
                <a
                    href="{{ route('unidades-adquiridas.show', $unidad) }}"
                    class="mt-2 inline-flex font-semibold text-oneshop-primary hover:underline"
                >
                    {{ $unidad->codigo_trazabilidad }}
                </a>
            @else
                <p class="mt-2 font-medium text-slate-900">
                    —
                </p>
            @endif
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Envío a Oruro
            </p>

            @if($envio)
                <a
                    href="{{ route('envios-importacion.show', $envio) }}"
                    class="mt-2 inline-flex font-semibold text-oneshop-primary hover:underline"
                >
                    {{ $envio->codigo }}
                </a>
            @else
                <p class="mt-2 font-medium text-slate-900">
                    No aplica
                </p>
            @endif
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Fecha ingreso a inventario
            </p>

            <p class="mt-2 font-medium text-slate-900">
                {{
                    $fechaIngresoInventario
                        ? $fechaIngresoInventario->format('d/m/Y H:i')
                        : '—'
                }}
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Incorporado por
            </p>

            <p class="mt-2 font-medium text-slate-900">
                {{ $incorporacion?->usuario?->name ?? 'Registro manual' }}
            </p>
        </div>

    </div>

</section>
