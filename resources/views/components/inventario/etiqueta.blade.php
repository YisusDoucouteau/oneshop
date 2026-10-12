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
        <div class="mx-auto aspect-[10/7] w-full max-w-lg overflow-hidden border-2 border-slate-900 bg-[#f59e42] p-5 shadow-sm sm:p-7">
            <div class="flex items-center justify-between gap-4 border-b-2 border-slate-900 pb-3">
                <p class="text-lg font-black tracking-[0.18em] text-slate-950">ONESHOP</p>
                <p class="text-right text-[10px] font-extrabold uppercase tracking-wide">Código: {{ $equipo->codigo_interno }}</p>
            </div>

            <div class="mt-3 space-y-1 text-xs">
                @foreach([
                    ['Marca / modelo', trim(($equipo->producto?->marca?->nombre ?? '') . ' ' . ($equipo->producto?->modelo ?? '')) ?: '—'],
                    ['Procesador', trim(($equipo->especificacion?->procesador ?? '') . ' ' . ($equipo->especificacion?->generacion_procesador ?? '')) ?: '—'],
                    ['Almacenamiento', $equipo->especificacion?->almacenamiento_gb ? $equipo->especificacion->almacenamiento_gb . ' GB ' . $equipo->especificacion->tipo_almacenamiento : '—'],
                    ['Memoria RAM', $equipo->especificacion?->ram_gb ? $equipo->especificacion->ram_gb . ' GB' : '—'],
                    ['Pantalla', $equipo->especificacion?->pantalla_pulgadas ? $equipo->especificacion->pantalla_pulgadas . ' pulgadas' : '—'],
                    ['Video', $equipo->especificacion?->tarjeta_grafica ?: '—'],
                    ['S/N', $equipo->serial_fabricante ?: '—'],
                ] as [$label, $value])
                    <div class="grid grid-cols-[130px_1fr] items-end gap-2 border-b border-slate-900/70 pb-1">
                        <span class="font-extrabold uppercase">{{ $label }}:</span>
                        <strong class="truncate text-sm">{{ $value }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="mx-auto mt-4 max-w-lg text-xs leading-5 text-slate-500">
            Formato de impresión: 100 × 70 mm. Los campos se completan automáticamente con la ficha técnica registrada; los datos faltantes quedan marcados con una línea.
        </p>
    </div>
</section>
