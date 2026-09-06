@props([
    'title',
    'value',
    'description' => null,
    'icon' => 'package',
    'color' => 'blue',
])


<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">

    <div class="flex items-start justify-between">

        <div>

            <p class="text-sm font-medium text-slate-500">
                {{ $title }}
            </p>


            <p class="mt-3 text-3xl font-bold text-slate-900">
                {{ $value }}
            </p>


            @if($description)

                <p class="mt-2 text-sm text-slate-400">
                    {{ $description }}
                </p>

            @endif

        </div>


        <div class="
            flex h-12 w-12 items-center justify-center
            rounded-xl
            bg-{{ $color }}-50
            text-{{ $color }}-600
        ">

            <x-ui.icon
                name="{{ $icon }}"
                size="24"
            />

        </div>


    </div>

</div>