@props([
    'label',
    'value',
    'percentage' => 0,
])

@php
    $percentage =
        max(
            0,
            min(
                100,
                (int) $percentage
            )
        );
@endphp


<div
    class="
        rounded-xl
        border
        border-slate-200
        bg-slate-50/60
        px-4
        py-3
        transition
        duration-200
        hover:border-blue-200
        hover:bg-oneshop-soft
    "
>

    <div
        class="
            flex
            items-center
            justify-between
            gap-4
        "
    >

        <div class="min-w-0">

            <p
                class="
                    truncate
                    text-sm
                    font-semibold
                    text-slate-800
                "
            >
                {{ $label }}
            </p>

        </div>


        <div
            class="
                flex
                shrink-0
                items-center
                gap-3
            "
        >

            <span
                class="
                    text-sm
                    font-semibold
                    text-slate-700
                "
            >
                {{ $value }}
            </span>


            <span
                class="
                    min-w-12
                    rounded-lg
                    bg-oneshop-light
                    px-2
                    py-1
                    text-center
                    text-xs
                    font-bold
                    text-oneshop-dark
                "
            >
                {{ $percentage }}%
            </span>

        </div>

    </div>


    <div
        class="
            mt-2.5
            h-2
            overflow-hidden
            rounded-full
            bg-slate-200
        "
    >

        <div
            class="
                h-full
                rounded-full
                bg-oneshop-primary
                transition-all
                duration-500
            "

            style="
                width:
                {{ $percentage }}%;
            "
        ></div>

    </div>

</div>