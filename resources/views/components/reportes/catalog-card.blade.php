@props([
    'title',
    'description',
    'href',
    'eyebrow' => 'Reporte',
    'icon' => 'chart',
])

<a
    href="{{ $href }}"
    {{ $attributes->class('group flex min-h-40 items-start justify-between gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md') }}
>
    <div class="flex min-w-0 gap-4">
        <span class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700">
            <x-ui.icon name="{{ $icon }}" size="19" />
        </span>

        <div class="min-w-0">
            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-slate-400">{{ $eyebrow }}</p>
            <h3 class="mt-1.5 text-lg font-black text-slate-950">{{ $title }}</h3>
            <p class="mt-2 max-w-xl text-sm leading-6 text-slate-600">{{ $description }}</p>
        </div>
    </div>

    <span class="mt-1 text-xl font-bold text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-blue-700">→</span>
</a>
