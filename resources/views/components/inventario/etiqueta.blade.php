<section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-950">Etiqueta del equipo</h2>
            <p class="mt-1 text-sm text-slate-500">
                Identificación física permanente para inventario y trazabilidad.
            </p>
        </div>

        <a
            href="{{ route('inventario.etiqueta', $equipo) }}"
            target="_blank"
            rel="noopener"
            class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700"
        >
            Imprimir etiqueta
        </a>
    </div>

    <div class="p-6 sm:p-8">
        <div class="relative mx-auto aspect-[10/7] w-full max-w-lg overflow-hidden bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
            <div class="absolute left-4 top-4 h-28 w-36 border-l-4 border-t-4 border-[#0755b8] sm:left-6 sm:top-6"></div>
            <div class="relative flex h-10 items-start justify-end">
                <div class="h-9 w-36 overflow-hidden">
                    <img src="{{ asset('images/oneshop/logo.png') }}" alt="OneShop" class="block w-36 -translate-y-[27px]">
                </div>
            </div>

            <div class="relative mt-1 space-y-1 text-xs">
                @foreach([
                    ['Marca / modelo', trim(($equipo->producto?->marca?->nombre ?? '') . ' ' . ($equipo->producto?->modelo ?? '')) ?: '—'],
                    ['Procesador', trim(($equipo->especificacion?->procesador ?? '') . ' ' . ($equipo->especificacion?->generacion_procesador ?? '')) ?: '—'],
                    ['Almacenamiento', $equipo->especificacion?->almacenamiento_gb ? $equipo->especificacion->almacenamiento_gb . ' GB ' . $equipo->especificacion->tipo_almacenamiento : '—'],
                    ['Memoria RAM', $equipo->especificacion?->ram_gb ? $equipo->especificacion->ram_gb . ' GB' : '—'],
                    ['Pantalla', $equipo->especificacion?->pantalla_pulgadas ? $equipo->especificacion->pantalla_pulgadas . ' pulgadas' : '—'],
                    ['Video', $equipo->especificacion?->tarjeta_grafica ?: '—'],
                ] as [$label, $value])
                    <div class="grid grid-cols-[130px_1fr] items-center gap-2">
                        <span class="font-black uppercase text-[#0755b8]">{{ $label }}:</span>
                        <strong class="truncate border border-blue-600 px-2 py-1 text-center text-sm text-black">{{ $value }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="relative mt-2 grid grid-cols-2 gap-3 text-[10px]">
                <p class="truncate border-t border-blue-200 pt-1"><span class="font-black uppercase text-[#0755b8]">Código:</span> <strong>{{ $equipo->codigo_interno }}</strong></p>
                <p class="truncate border-t border-blue-200 pt-1"><span class="font-black uppercase text-[#0755b8]">S/N:</span> <strong>{{ $equipo->serial_fabricante ?: '—' }}</strong></p>
            </div>
        </div>

        <p class="mx-auto mt-4 max-w-lg text-xs leading-5 text-slate-500">
            Formato de impresión: 100 × 70 mm, adaptado a la etiqueta institucional de OneShop. Los campos se completan automáticamente con la ficha técnica registrada.
        </p>
    </div>
</section>
