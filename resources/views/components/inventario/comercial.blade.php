<section
    class="
    rounded-2xl
    border
    border-slate-200
    bg-white
    shadow-sm
    "
>
    <div class="flex flex-col gap-4 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-semibold text-slate-950">
                Información comercial
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Datos relacionados con precio, disponibilidad y condiciones comerciales del equipo.
            </p>
        </div>

        @if(auth()->user()?->tienePermiso('precios.ver'))
            <a
                href="{{ route('precios.equipos.show', $equipo) }}"
                class="inline-flex items-center justify-center rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                data-testid="gestionar-precio"
            >
                Gestionar precio
            </a>
        @endif
    </div>

    @php
        $precio = $equipo->precioVigente;

        $diferencia = null;

        if (
            $precio
            && $precio->precio_minimo_autorizado !== null
        ) {
            $diferencia =
                $precio->precio_publico
                -
                $precio->precio_minimo_autorizado;
        }
    @endphp

    <div class="grid gap-5 p-6 lg:grid-cols-3">
        <div class="rounded-2xl bg-slate-50 p-5">
            <div class="flex items-center gap-3">
                <div
                    class="
                    flex
                    h-10
                    w-10
                    items-center
                    justify-center
                    rounded-xl
                    bg-blue-100
                    text-blue-600
                    "
                >
                    <x-ui.icon
                        name="chart"
                        size="22"
                    />
                </div>

                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Precio vigente
                </p>
            </div>

            @if($precio)
                <p class="mt-5 text-3xl font-bold text-slate-900">
                    Bs {{ number_format($precio->precio_publico, 2) }}
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Precio público actual
                </p>
            @else
                <p class="mt-5 font-medium text-slate-400">
                    Sin precio registrado
                </p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Precio sugerido
            </p>

            @if($precio)
                <p class="mt-3 text-xl font-bold text-slate-900">
                    Bs {{ number_format($precio->precio_sugerido, 2) }}
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Referencia comercial
                </p>
            @else
                <p class="mt-3 text-slate-400">—</p>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-100 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">
                Mínimo autorizado
            </p>

            @if($precio && $precio->precio_minimo_autorizado !== null)
                <p class="mt-3 text-xl font-bold text-slate-900">
                    Bs {{ number_format($precio->precio_minimo_autorizado, 2) }}
                </p>

                <p class="mt-1 text-sm text-slate-500">
                    Límite comercial
                </p>
            @else
                <p class="mt-3 text-slate-400">—</p>
            @endif
        </div>
    </div>

    <div class="grid gap-5 px-6 pb-6 md:grid-cols-3">
        <div class="rounded-xl bg-blue-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">
                Margen negociable
            </p>

            <p class="mt-2 font-bold text-blue-900">
                @if($diferencia !== null)
                    Bs {{ number_format($diferencia, 2) }}
                @else
                    —
                @endif
            </p>
        </div>

        <div class="rounded-xl border border-slate-100 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Estado comercial
            </p>

            <p class="mt-2 font-semibold text-slate-900">
                {{ $equipo->estadoActual?->nombre ?? '—' }}
            </p>
        </div>

        <div class="rounded-xl border border-slate-100 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                Código interno
            </p>

            <p class="mt-2 font-semibold text-slate-900">
                {{ $equipo->codigo_interno }}
            </p>
        </div>
    </div>

    @if($precio)
        <div class="border-t border-slate-100 px-6 py-5">
            <p class="text-sm text-slate-500">
                Vigente desde:
                <span class="font-medium text-slate-700">
                    {{ $precio->vigente_desde?->format('d/m/Y') ?? '—' }}
                </span>
            </p>

            @if($precio->observacion)
                <p class="mt-2 text-sm text-slate-500">
                    {{ $precio->observacion }}
                </p>
            @endif
        </div>
    @endif
</section>
