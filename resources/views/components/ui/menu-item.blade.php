@props([
    'route',
    'label',
    'icon',
    'permission' => null,
])


@if(!$permission || auth()->user()?->tienePermiso($permission))

<a
    href="{{ route($route) }}"
    class="
        group
        flex
        items-center
        gap-3
        rounded-xl
        px-4
        py-3
        text-sm
        font-medium
        transition-all
        duration-200

        {{ request()->routeIs(str_replace('.index','.*',$route))
            ? 'bg-white text-blue-950 shadow-lg'
            : 'text-blue-100 hover:bg-white/10'
        }}
    "
>

    <span
        class="
            flex
            h-8
            w-8
            items-center
            justify-center
            rounded-lg
            bg-white/10
            group-hover:bg-white/20
        "
    >

        <x-ui.icon
            name="{{ $icon }}"
            size="18"
        />

    </span>


    <span>
        {{ $label }}
    </span>


</a>

@endif