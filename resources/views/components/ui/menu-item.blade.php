@props([
    'route',
    'label',
    'icon',
    'permission' => null,
])

@php
    $routePattern =
        str_ends_with($route, '.index')
            ? str_replace('.index', '.*', $route)
            : $route;

    $activo = request()->routeIs($routePattern);
@endphp

@if(
    !$permission
    || auth()->user()?->tienePermiso($permission)
)

    <a
        href="{{ route($route) }}"
        @if($activo)
            aria-current="page"
        @endif

        class="
            group
            relative
            flex
            min-h-11
            items-center
            gap-3
            rounded-lg
            border
            px-3
            py-2.5
            text-sm
            font-semibold
            transition-all
            duration-150

            {{
                $activo
    ? 'border-blue-300 bg-white text-oneshop-dark shadow-sm'
    : 'border-transparent text-slate-700 hover:border-blue-200 hover:bg-white/60 hover:text-oneshop-dark'
            }}
        "
    >

        @if($activo)
            <span
                class="
                    absolute
                    bottom-2
                    left-0
                    top-2
                    w-1
                    rounded-r-full
                    bg-oneshop-primary
                "
            ></span>
        @endif


        <span
            class="
                flex
                h-8
                w-8
                shrink-0
                items-center
                justify-center

                {{
                    $activo
                        ? 'text-oneshop-primary'
                        : 'text-slate-500 group-hover:text-oneshop-primary'
                }}
            "
        >

            <x-ui.icon
                name="{{ $icon }}"
                size="19"
            />

        </span>


        <span class="truncate">
            {{ $label }}
        </span>

    </a>

@endif