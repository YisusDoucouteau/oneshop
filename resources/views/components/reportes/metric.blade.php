@props([
    'label',
    'value',
    'caption' => null,
    'tone' => 'slate',
    'large' => false,
])

@php
    $toneClasses = match ($tone) {
        'emerald' => 'border-emerald-200 bg-emerald-50/80 text-emerald-950',
        'blue' => 'border-blue-200 bg-blue-50/80 text-blue-950',
        'amber' => 'border-amber-200 bg-amber-50/80 text-amber-950',
        'violet' => 'border-violet-200 bg-violet-50/80 text-violet-950',
        default => 'border-slate-200 bg-white text-slate-950',
    };
@endphp

<div {{ $attributes->class([
    'rounded-2xl border shadow-sm',
    $large ? 'p-5' : 'p-4',
    $toneClasses,
]) }}>
    <p class="text-[11px] font-black uppercase tracking-[0.12em] text-slate-500">{{ $label }}</p>
    <p class="mt-2 font-black tracking-tight {{ $large ? 'text-3xl' : 'text-2xl' }}">{{ $value }}</p>

    @if($caption)
        <p class="mt-1.5 text-xs leading-5 text-slate-500">{{ $caption }}</p>
    @endif
</div>
