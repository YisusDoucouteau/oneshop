<x-layouts.oneshop
    title="Gestión de precio | OneShop"
    page-title="Gestión de precio"
>

<div class="space-y-6">

    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:flex-row lg:items-center lg:justify-between">
        <div>
            <a
                href="{{ route('inventario.show', $equipo) }}"
                class="text-sm font-medium text-blue-600 hover:text-blue-700"
            >
                ← Volver al equipo
            </a>

            <h1 class="mt-2 text-2xl font-bold text-slate-950">
                {{ $equipo->producto?->nombre ?? 'Equipo' }}
                {{ $equipo->producto?->modelo }}
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $equipo->codigo_interno }}
                @if($equipo->serial_fabricante)
                    · Serie {{ $equipo->serial_fabricante }}
                @endif
            </p>
        </div>

        <div class="rounded-xl bg-slate-50 px-5 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Costo real actual
            </p>
            <p class="mt-1 text-2xl font-bold text-slate-950">
                Bs {{ number_format($costo['costo_total'], 2) }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800">
            <p class="font-semibold">Revisa los datos ingresados.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $fuentesCosto = [
            'HISTORIAL_UNIDAD' =>
                'Compra, importación y preparación',

            'DETALLE_LOTE' =>
                'Costo de compra del lote',

            'SNAPSHOT_PRECIO_LEGACY' =>
                'Costo histórico registrado',

            'SIN_INFORMACION' =>
                'Sin costo base disponible',
        ];

        $fuenteCosto =
            $fuentesCosto[$costo['fuente_base']]
            ?? 'Costo registrado por el sistema';
    @endphp

    <div class="grid gap-6 xl:grid-cols-3">

        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="font-semibold text-slate-950">
                    Costo y precio vigente
                </h2>
                <p class="mt-1 text-sm text-slate-500">
                    El costo se calcula desde la trazabilidad económica del equipo.
                </p>
            </div>

            <div class="grid gap-4 p-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Costo base
                    </p>
                    <p class="mt-2 text-lg font-bold text-slate-900">
                        Bs {{ number_format($costo['costo_base'], 2) }}
                    </p>
                </div>

                <div class="rounded-xl bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Costos posteriores
                    </p>
                    <p class="mt-2 text-lg font-bold text-slate-900">
                        Bs {{ number_format($costo['costos_posteriores'], 2) }}
                    </p>
                </div>

                <div class="rounded-xl bg-blue-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                        Costo real
                    </p>
                    <p class="mt-2 text-lg font-bold text-blue-950">
                        Bs {{ number_format($costo['costo_total'], 2) }}
                    </p>
                </div>

                <div class="rounded-xl border border-slate-100 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                        Origen del costo
                    </p>
                    <p class="mt-2 text-sm font-semibold text-slate-800">
                        {{ $fuenteCosto }}
                    </p>
                </div>
            </div>

            @if(!$costo['completo'] || count($costo['advertencias']) > 0)
                <div class="mx-6 mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <p class="font-semibold">Costo con observaciones</p>
                    @foreach($costo['advertencias'] as $advertencia)
                        <p class="mt-1">{{ $advertencia }}</p>
                    @endforeach
                </div>
            @endif

            <div class="border-t border-slate-100 px-6 py-5">
                @if($equipo->precioVigente)
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Precio público
                            </p>
                            <p class="mt-1 text-xl font-bold text-slate-950">
                                Bs {{ number_format($equipo->precioVigente->precio_publico, 2) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Sugerido
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                Bs {{ number_format($equipo->precioVigente->precio_sugerido, 2) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                Costo al fijar este precio
                            </p>
                            <p class="mt-1 font-semibold text-slate-800">
                                Bs {{ number_format($equipo->precioVigente->costo_total_snapshot, 2) }}
                            </p>
                        </div>
                    </div>
                @else
                    <p class="text-sm font-medium text-slate-500">
                        Este equipo todavía no tiene un precio publicado.
                    </p>
                @endif
            </div>
        </section>

        @if(auth()->user()?->tienePermiso('precios.modificar'))
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="font-semibold text-slate-950">
                        Nuevo precio
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Evalúa antes de publicar.
                    </p>
                </div>

                @php
                    $valorSugerido =
                        old(
                            'precio_sugerido',
                            $formulario['precio_sugerido']
                                ?? $equipo->precioVigente?->precio_sugerido
                                ?? ''
                        );

                    $valorPublico =
                        old(
                            'precio_publico',
                            $formulario['precio_publico']
                                ?? $equipo->precioVigente?->precio_publico
                                ?? ''
                        );

                    $valorMinimo =
                        old(
                            'precio_minimo_autorizado',
                            $formulario['precio_minimo_autorizado']
                                ?? $equipo->precioVigente?->precio_minimo_autorizado
                                ?? ''
                        );

                    $valorObservacion =
                        old(
                            'observacion',
                            $formulario['observacion']
                                ?? ''
                        );
                @endphp

                <form class="space-y-4 p-6" method="POST">
                    @csrf

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Precio sugerido
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="precio_sugerido"
                            value="{{ $valorSugerido }}"
                            class="mt-1 w-full rounded-xl border-slate-300"
                            required
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Precio público
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            name="precio_publico"
                            value="{{ $valorPublico }}"
                            class="mt-1 w-full rounded-xl border-slate-300"
                            required
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Mínimo autorizado
                        </label>
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            name="precio_minimo_autorizado"
                            value="{{ $valorMinimo }}"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >
                    </div>

                    <div>
                        <label class="text-sm font-medium text-slate-700">
                            Observación
                        </label>
                        <textarea
                            name="observacion"
                            rows="3"
                            class="mt-1 w-full rounded-xl border-slate-300"
                        >{{ $valorObservacion }}</textarea>
                    </div>

                    <div class="grid gap-3">
                        <button
                            type="submit"
                            formaction="{{ route('precios.equipos.evaluar', $equipo) }}"
                            class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                        >
                            Evaluar propuesta
                        </button>

                        <button
                            type="submit"
                            formaction="{{ route('precios.equipos.store', $equipo) }}"
                            class="rounded-xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Registrar precio
                        </button>
                    </div>
                </form>
            </section>
        @endif
    </div>

    @if($evaluacion)
        @php
            $estado = $evaluacion['estado'] ?? 'REQUIERE_REVISION';

            $estadosLegibles = [
                'APROBABLE' =>
                    'Aprobable',

                'REQUIERE_REVISION' =>
                    'Revisión administrativa',

                'REQUIERE_AUTORIZACION' =>
                    'Requiere autorización',

                'NO_CUMPLE_POLITICA' =>
                    'Fuera de política',

                'NO_RECOMENDADA' =>
                    'No recomendada',
            ];

            $estadoLegible =
                $estadosLegibles[$estado]
                ?? 'Revisión necesaria';

            $tono = match ($estado) {
                'APROBABLE' =>
                    'verde',

                'REQUIERE_REVISION',
                'REQUIERE_AUTORIZACION' =>
                    'ambar',

                default =>
                    'rojo',
            };

            $clasesContenedor = match ($tono) {
                'verde' =>
                    'border-emerald-200 bg-emerald-50',

                'ambar' =>
                    'border-amber-200 bg-amber-50',

                default =>
                    'border-red-200 bg-red-50',
            };

            $clasesTexto = match ($tono) {
                'verde' =>
                    'text-emerald-700',

                'ambar' =>
                    'text-amber-700',

                default =>
                    'text-red-700',
            };

            $porcentajeDescuento =
                (float) (
                    $evaluacion['porcentaje_descuento']
                    ?? 0
                );

            if ($porcentajeDescuento > 0) {
                $etiquetaVariacion =
                    'Descuento';

                $valorVariacion =
                    $porcentajeDescuento;
            } elseif ($porcentajeDescuento < 0) {
                $etiquetaVariacion =
                    'Incremento';

                $valorVariacion =
                    abs($porcentajeDescuento);
            } else {
                $etiquetaVariacion =
                    'Variación';

                $valorVariacion =
                    0;
            }
        @endphp

        <section class="rounded-2xl border {{ $clasesContenedor }} p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide {{ $clasesTexto }}">
                        Evaluación comercial
                    </p>
                    <h2 class="mt-1 text-xl font-bold text-slate-950">
                        {{ $estadoLegible }}
                    </h2>
                </div>

                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div>
                        <p class="text-xs text-slate-500">Propuesto</p>
                        <p class="font-bold text-slate-900">
                            Bs {{ number_format($evaluacion['precio_propuesto'], 2) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Ganancia</p>
                        <p class="font-bold text-slate-900">
                            Bs {{ number_format($evaluacion['ganancia'], 2) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">Utilidad</p>
                        <p class="font-bold text-slate-900">
                            {{ number_format($evaluacion['porcentaje_utilidad'], 2) }} %
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-500">
                            {{ $etiquetaVariacion }}
                        </p>
                        <p class="font-bold text-slate-900">
                            {{ number_format($valorVariacion, 2) }} %
                        </p>
                    </div>
                </div>
            </div>

            @if($evaluacion['politica'])
                <p class="mt-4 text-sm text-slate-600">
                    Política aplicada:
                    <span class="font-semibold">
                        {{ $evaluacion['politica']['nombre'] }}
                    </span>
                </p>
            @else
                <p class="mt-4 text-sm text-slate-600">
                    No existe una política comercial aplicable para esta antigüedad/categoría.
                </p>
            @endif
        </section>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <h2 class="font-semibold text-slate-950">
                Historial de precios
            </h2>
            <p class="mt-1 text-sm text-slate-500">
                Cada registro conserva el costo conocido en el momento de la decisión.
            </p>
        </div>

        @if($historial->isEmpty())
            <div class="p-6 text-sm text-slate-500">
                No existen precios registrados.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Desde</th>
                            <th class="px-6 py-3">Precio público</th>
                            <th class="px-6 py-3">Costo al fijar precio</th>
                            <th class="px-6 py-3">Mínimo</th>
                            <th class="px-6 py-3">Estado</th>
                            <th class="px-6 py-3">Registrado por</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($historial as $precio)
                            <tr>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $precio->vigente_desde?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-950">
                                    Bs {{ number_format($precio->precio_publico, 2) }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    Bs {{ number_format($precio->costo_total_snapshot, 2) }}
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $precio->precio_minimo_autorizado !== null
                                        ? 'Bs ' . number_format($precio->precio_minimo_autorizado, 2)
                                        : '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($precio->vigente)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Vigente
                                        </span>
                                    @else
                                        <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Histórico
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $precio->aprobadoPor?->name ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</div>

</x-layouts.oneshop>
