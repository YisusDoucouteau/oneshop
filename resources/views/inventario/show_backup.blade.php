<x-layouts.oneshop
    :title="$equipo->codigo_interno . ' | OneShop'"
    page-title="Detalle del equipo"
>

    {{-- Volver --}}
    <div class="mb-6">
        <a
            href="{{ route('inventario.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-slate-950"
        >
            ← Volver al inventario
        </a>
    </div>
@if(session('success'))
    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">

        <p class="text-sm font-semibold text-emerald-800">
            {{ session('success') }}
        </p>

    </div>
@endif

    {{-- Encabezado --}}
   {{-- Encabezado del equipo --}}

<x-inventario.encabezado
    :equipo="$equipo"
/>

    {{-- Resumen del equipo --}}

<x-inventario.resumen
    :equipo="$equipo"
/>



    <div class="grid gap-6 xl:grid-cols-3">

        {{-- Columna principal --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- Identificación --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="font-semibold text-slate-950">
                        Identificación
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Información general del equipo físico.
                    </p>
                </div>

                <div class="grid gap-x-8 gap-y-6 p-6 sm:grid-cols-2">

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Código interno
                        </p>

                        <p class="mt-2 font-medium text-slate-900">
                            {{ $equipo->codigo_interno }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Serial fabricante
                        </p>

                        <p class="mt-2 font-medium text-slate-900">
                            {{ $equipo->serial_fabricante ?: 'No registrado' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Categoría
                        </p>

                        <p class="mt-2 font-medium text-slate-900">
                            {{ $equipo->producto?->categoria?->nombre ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Estado actual
                        </p>

                        <p class="mt-2 font-medium text-slate-900">
                            {{ $equipo->estadoActual?->nombre ?? '—' }}
                        </p>
                    </div>

                </div>

                @if($equipo->observacion)
                    <div class="border-t border-slate-100 px-6 py-5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Observación
                        </p>

                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            {{ $equipo->observacion }}
                        </p>
                    </div>
                @endif

            </section>


            {{-- Especificaciones --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-5">
                    <h2 class="font-semibold text-slate-950">
                        Especificaciones técnicas
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        Configuración registrada para este equipo.
                    </p>
                </div>

                @if($equipo->especificacion)

                    <div class="grid gap-x-8 gap-y-6 p-6 sm:grid-cols-2 lg:grid-cols-3">

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Procesador
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                {{ $equipo->especificacion->procesador ?: '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Generación
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                {{ $equipo->especificacion->generacion_procesador ?: '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                RAM
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                @if($equipo->especificacion->ram_gb)
                                    {{ $equipo->especificacion->ram_gb }} GB
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Almacenamiento
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                @if($equipo->especificacion->almacenamiento_gb)
                                    {{ $equipo->especificacion->almacenamiento_gb }} GB
                                    {{ $equipo->especificacion->tipo_almacenamiento }}
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Pantalla
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                @if($equipo->especificacion->pantalla_pulgadas)
                                    {{ $equipo->especificacion->pantalla_pulgadas }}"
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Resolución
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                {{ $equipo->especificacion->resolucion ?: '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Sistema operativo
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                {{ $equipo->especificacion->sistema_operativo ?: '—' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Batería
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                @if($equipo->especificacion->bateria_porcentaje !== null)
                                    {{ $equipo->especificacion->bateria_porcentaje }}%
                                @else
                                    —
                                @endif
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Gráficos
                            </p>

                            <p class="mt-2 font-medium text-slate-900">
                                {{ $equipo->especificacion->tarjeta_grafica ?: 'Integrados / no registrado' }}
                            </p>
                        </div>

                    </div>

                @else

                    <div class="p-10 text-center">
                        <p class="font-medium text-slate-800">
                            Sin especificaciones registradas
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            Este equipo todavía no cuenta con una ficha técnica.
                        </p>
                    </div>

                @endif

            </section>

        </div>


        {{-- Sidebar de ficha --}}
        <div class="space-y-6">
            {{-- Procedencia --}}
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 px-5 py-4">
        <h2 class="font-semibold text-slate-950">
            Procedencia
        </h2>
    </div>

    <div class="space-y-5 p-5">

        @if($equipo->detalleLote?->lote)

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Lote
                </p>

                <p class="mt-2 font-semibold text-slate-900">
                    {{ $equipo->detalleLote->lote->codigo }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Origen
                </p>

                <p class="mt-2 font-medium text-slate-900">
                    {{ $equipo->detalleLote->lote->origen ?: 'No registrado' }}
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Proveedor
                </p>

                <p class="mt-2 font-medium text-slate-900">
                    {{ $equipo->detalleLote->lote->proveedor?->nombre ?? 'No registrado' }}
                </p>
            </div>

            @if($equipo->detalleLote->lote->referencia_compra)
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                        Referencia de compra
                    </p>

                    <p class="mt-2 font-medium text-slate-900">
                        {{ $equipo->detalleLote->lote->referencia_compra }}
                    </p>
                </div>
            @endif

        @else

            <div class="rounded-xl bg-slate-50 p-4">

                <p class="text-sm font-semibold text-slate-800">
                    Sin lote asociado
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Este registro no cuenta todavía con información de importación o procedencia.
                </p>

            </div>

        @endif

         </div>

        </section>
            {{-- Comercial --}}
            <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-5 py-4">
                    <h2 class="font-semibold text-slate-950">
                        Información comercial
                    </h2>
                </div>

                <div class="space-y-5 p-5">

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                            Precio público
                        </p>

                        @if($equipo->precioVigente)
                            <p class="mt-2 text-xl font-bold text-slate-950">
                                Bs {{ number_format(
                                    (float) $equipo->precioVigente->precio_publico,
                                    2
                                ) }}
                            </p>
                        @else
                            <p class="mt-2 font-medium text-slate-400">
                                Sin precio vigente
                            </p>
                        @endif
                    </div>


                    @if(
                        $equipo->precioVigente &&
                        auth()->user()->tienePermiso('precios.autorizar_descuento')
                    )
                        <div class="border-t border-slate-100 pt-5">

                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Precio mínimo autorizado
                            </p>

                            <p class="mt-2 font-semibold text-slate-900">
                                @if($equipo->precioVigente->precio_minimo_autorizado)
                                    Bs {{ number_format(
                                        (float) $equipo->precioVigente->precio_minimo_autorizado,
                                        2
                                    ) }}
                                @else
                                    —
                                @endif
                            </p>

                        </div>
                    @endif


                    @if(
                        $equipo->precioVigente &&
                        auth()->user()->tienePermiso('finanzas.ver')
                    )
                        <div class="border-t border-slate-100 pt-5">

                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                                Costo registrado
                            </p>

                            <p class="mt-2 font-semibold text-slate-900">
                                @if($equipo->precioVigente->costo_total_snapshot)
                                    Bs {{ number_format(
                                        (float) $equipo->precioVigente->costo_total_snapshot,
                                        2
                                    ) }}
                                @else
                                    —
                                @endif
                            </p>

                        </div>
                    @endif

                </div>

            </section>

{{-- Trazabilidad --}}
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">

    <div class="border-b border-slate-200 px-5 py-4">

        <div class="flex items-center justify-between">

            <div>
                <h2 class="font-semibold text-slate-950">
                    Trazabilidad
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Historial operativo del equipo.
                </p>
            </div>

            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                {{ $eventosTrazabilidad->count() }}
            </span>

        </div>

    </div>


    <div class="p-5">

        @if($eventosTrazabilidad->isNotEmpty())

            <div class="relative">

                <div class="absolute bottom-2 left-[7px] top-2 w-px bg-slate-200"></div>

                <div class="space-y-6">

                    @foreach($eventosTrazabilidad as $evento)

                        <div class="relative pl-8">

                            <div class="absolute left-0 top-1.5 h-[15px] w-[15px] rounded-full border-4 border-white bg-slate-900 shadow-sm"></div>

                            <div>

                                <div class="flex flex-col gap-1">

                                    <p class="text-sm font-semibold text-slate-900">
                                        {{ $evento['titulo'] }}
                                    </p>

                                    <p class="text-xs font-medium text-slate-400">
                                        {{ $evento['fecha']->format('d/m/Y H:i') }}
                                    </p>

                                </div>


                                @if($evento['detalle'])
                                    <p class="mt-2 text-sm leading-5 text-slate-600">
                                        {{ $evento['detalle'] }}
                                    </p>
                                @endif


                                @if($evento['usuario'])
                                    <p class="mt-2 text-xs text-slate-500">
                                        Por
                                        <span class="font-medium text-slate-700">
                                            {{ $evento['usuario'] }}
                                        </span>
                                    </p>
                                @endif


                                @if($evento['observacion'])
                                    <div class="mt-3 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-500">
                                        {{ $evento['observacion'] }}
                                    </div>
                                @endif

                            </div>

                        </div>

                    @endforeach

                </div>

            </div>

        @else

            <div class="py-6 text-center">

                <p class="font-medium text-slate-800">
                    Sin eventos registrados
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    La actividad del equipo aparecerá aquí.
                </p>

            </div>

        @endif

    </div>

</section>


            {{-- QR futuro --}}
            <section class="rounded-2xl border border-dashed border-slate-300 bg-white p-5 text-center">

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-xl bg-slate-100 text-xl">
                    ▦
                </div>

                <p class="mt-3 font-semibold text-slate-900">
                    Identificación QR
                </p>

                <p class="mt-1 text-sm leading-5 text-slate-500">
                    Se habilitará para consultar la trazabilidad física del equipo.
                </p>

            </section>

        </div>

    </div>

</x-layouts.oneshop>