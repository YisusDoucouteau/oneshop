<x-layouts.oneshop
    title="Reportes | OneShop"
    page-title="Reportes"
>
<div class="space-y-7">
    <section class="flex flex-wrap items-end justify-between gap-4">
        <div class="max-w-3xl">
            <p class="text-xs font-black uppercase tracking-[0.16em] text-blue-700">Centro de información</p>
            <h1 class="mt-2 text-3xl font-black tracking-tight text-slate-950">Reportes</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Consulta información del proceso OneShop y genera documentos para control, seguimiento y toma de decisiones.
            </p>
        </div>

        <a
            href="{{ route('dashboard') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50"
        >
            <x-ui.icon name="home" size="17" />
            Ir al inicio
        </a>
    </section>

    <section>
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-[0.14em] text-slate-400">Disponibles</p>
                <h2 class="mt-1 text-xl font-black text-slate-950">Reportes habilitados</h2>
            </div>
            <p class="text-xs font-bold text-slate-400">5 activos</p>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-reportes.catalog-card
                title="Ventas"
                description="Consulta operaciones de venta por periodo, cliente, vendedor y estado, con información histórica y salidas formales."
                :href="route('reportes.ventas.index')"
                eyebrow="Comercial"
                icon="chart"
            />

            <x-reportes.catalog-card
                title="Inventario"
                description="Consulta equipos por código, serial, producto, almacén y estado para control operativo y administrativo."
                :href="route('reportes.inventario.index')"
                eyebrow="Operación"
                icon="package"
            />

            <x-reportes.catalog-card
                title="Abastecimiento y logística"
                description="Consolida el avance de los lotes desde la compra y recepción en Cochabamba hasta el envío, recepción e incorporación en Oruro."
                :href="route('reportes.abastecimiento-logistica.index')"
                eyebrow="Logística"
                icon="package"
            />

            <x-reportes.catalog-card
                title="Garantías y postventa"
                description="Controla casos de postventa, tiempos de atención, diagnósticos, resoluciones y reemplazos de equipos."
                :href="route('reportes.garantias-postventa.index')"
                eyebrow="Postventa"
                icon="shield"
            />

            <x-reportes.catalog-card
                title="Trazabilidad de equipos"
                description="Reconstruye el expediente cronológico de cada equipo desde su adquisición y preparación hasta inventario, venta, pagos y postventa."
                :href="route('reportes.trazabilidad.index')"
                eyebrow="Trazabilidad"
                icon="search"
            />

        </div>
    </section>

</div>
</x-layouts.oneshop>
