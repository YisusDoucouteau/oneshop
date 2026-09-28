@props([
    'title',
    'value',
    'description' => null,
    'icon' => 'package',
    'color' => 'blue',
])

@php
    $styles = [

        'blue' => [
            'background' => 'bg-oneshop-light',
            'text' => 'text-oneshop-primary',
            'border' => 'border-blue-100',
        ],

        'green' => [
            'background' => 'bg-emerald-50',
            'text' => 'text-emerald-700',
            'border' => 'border-emerald-100',
        ],

        'orange' => [
            'background' => 'bg-amber-50',
            'text' => 'text-amber-700',
            'border' => 'border-amber-100',
        ],

        'red' => [
            'background' => 'bg-red-50',
            'text' => 'text-red-700',
            'border' => 'border-red-100',
        ],

        'slate' => [
            'background' => 'bg-slate-100',
            'text' => 'text-slate-700',
            'border' => 'border-slate-200',
        ],

    ];

    $style =
        $styles[$color]
        ?? $styles['blue'];
@endphp


<div
    class="
        group
        rounded-2xl
        border
        border-slate-200
        bg-white
        p-6

        shadow-sm

        transition
        duration-200

        hover:border-blue-200
        hover:shadow-oneshop
    "
>

    <div
        class="
            flex
            items-start
            justify-between
            gap-5
        "
    >

        <div class="min-w-0">

            <p
                class="
                    text-sm
                    font-semibold
                    text-slate-700
                "
            >
                {{ $title }}
            </p>


            <p
                class="
                    mt-3
                    text-3xl
                    font-bold
                    tracking-tight
                    text-slate-950
                "
            >
                {{ $value }}
            </p>


            @if($description)

                <p
                    class="
                        mt-2
                        text-sm
                        font-medium
                        leading-5
                        text-slate-500
                    "
                >
                    {{ $description }}
                </p>

            @endif

        </div>


        <div
            class="
                flex
                h-12
                w-12
                shrink-0
                items-center
                justify-center

                rounded-xl
                border

                transition
                duration-200

                group-hover:scale-105

                {{ $style['background'] }}
                {{ $style['text'] }}
                {{ $style['border'] }}
            "
        >

            <x-ui.icon
                name="{{ $icon }}"
                size="23"
            />

        </div>

    </div>

</div>