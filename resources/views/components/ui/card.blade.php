@props([
    'padding' => true
])

<div {{ $attributes->merge([
    'class' =>
        'rounded-2xl border border-slate-200 bg-white shadow-oneshop ' .
        ($padding ? 'p-6' : '')
]) }}>
    {{ $slot }}
</div>