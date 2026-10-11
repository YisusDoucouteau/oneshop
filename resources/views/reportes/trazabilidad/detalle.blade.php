<x-layouts.oneshop
    title="Trazabilidad {{ $equipo->codigo_interno }} | OneShop"
    page-title="Trazabilidad del equipo"
>
<div class="space-y-7">
    <section class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Expediente cronológico</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">{{ $equipo->codigo_interno ?: 'Equipo #' . $equipo->id }}</h1>
            <p class="mt-2 text-sm text-slate-600">
                {{ $equipo->producto?->nombre ?: 'Producto no registrado' }}
                @if($equipo->producto?->modelo) · {{ $equipo->producto->modelo }} @endif
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('reportes.trazabilidad.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Volver</a>
            <a href="{{ route('reportes.trazabilidad.imprimir', $equipo) }}" target="_blank" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Imprimir</a>
            <a href="{{ route('reportes.trazabilidad.pdf', $equipo) }}" target="_blank" class="rounded-xl bg-blue-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-800">PDF</a>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        <x-reportes.metric label="Eventos registrados" :value="number_format($eventos->count())" caption="Historia cronológica consolidada" tone="blue" />
        <x-reportes.metric label="Estado actual" :value="$equipo->estadoActual?->nombre ?: 'Sin estado'" caption="Situación actual del equipo" tone="slate" />
        <x-reportes.metric label="Ubicación actual" :value="$equipo->almacenActual?->nombre ?: 'No registrada'" caption="Almacén operativo" tone="slate" />
        <x-reportes.metric label="Lote" :value="$lote?->codigo ?: 'Sin lote'" caption="{{ $proveedor ?: 'Procedencia sin proveedor' }}" tone="slate" />
        <x-reportes.metric label="Código trazabilidad" :value="$codigoTrazabilidad ?: 'No aplica'" caption="Unidad preinventario" tone="slate" />
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.1em] text-slate-400">Código interno</p>
                <p class="mt-1 font-bold text-slate-900">{{ $equipo->codigo_interno ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-[0.1em] text-slate-400">Serial fabricante</p>
                <p class="mt-1 font-bold text-slate-900">{{ $equipo->serial_fabricante ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-[0.1em] text-slate-400">Marca / modelo</p>
                <p class="mt-1 font-bold text-slate-900">{{ collect([$equipo->producto?->marca?->nombre, $equipo->producto?->modelo])->filter()->implode(' · ') ?: '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-[0.1em] text-slate-400">Condición física</p>
                <p class="mt-1 font-bold text-slate-900">{{ $equipo->condicionFisica?->nombre ?: 'No registrada' }}</p>
            </div>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <p class="text-xs font-black uppercase tracking-[0.12em] text-slate-400">Historia consolidada</p>
            <h2 class="mt-1 text-lg font-black text-slate-950">Línea de tiempo del equipo</h2>
            <p class="mt-1 text-sm text-slate-500">Los eventos se presentan en orden cronológico real y provienen de los módulos operativos del sistema.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($eventos as $evento)
                <article class="grid gap-3 px-5 py-5 md:grid-cols-[150px_180px_1fr]">
                    <div>
                        <div class="text-sm font-bold text-slate-900">{{ $evento['fecha']->format('d/m/Y') }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $evento['fecha']->format('H:i') }}</div>
                    </div>
                    <div>
                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black uppercase tracking-[0.06em] text-slate-700">
                            {{ str($evento['tipo'])->replace('_', ' ')->title() }}
                        </span>
                        @if($evento['usuario'])
                            <div class="mt-2 text-xs text-slate-500">{{ $evento['usuario'] }}</div>
                        @endif
                    </div>
                    <div>
                        <h3 class="font-black text-slate-950">{{ $evento['titulo'] }}</h3>
                        @if($evento['detalle'])
                            <p class="mt-1 text-sm leading-6 text-slate-600">{{ $evento['detalle'] }}</p>
                        @endif
                        @if($evento['observacion'])
                            <p class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-xs leading-5 text-slate-500"><strong>Observación:</strong> {{ $evento['observacion'] }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="px-5 py-12 text-center text-slate-500">No existen eventos de trazabilidad registrados para este equipo.</div>
            @endforelse
        </div>
    </section>
</div>
</x-layouts.oneshop>
