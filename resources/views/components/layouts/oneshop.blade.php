@props([
    'title' => 'OneShop',
    'pageTitle' => 'Panel de control',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'OneShop' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-100 text-slate-900 antialiased">

<div
    x-data="{ sidebarOpen: false }"
    class="min-h-screen"
>
    {{-- Overlay móvil --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-slate-950/50 lg:hidden"
    ></div>

    {{-- Sidebar --}}
        {{-- Sidebar --}}
<aside
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="
        fixed inset-y-0 left-0 z-50
        w-72
        transform
        bg-gradient-to-b
        from-blue-950
        to-blue-900
        text-white
        transition-transform
        duration-200
        lg:translate-x-0
    "
>

    {{-- Logo --}}
    <div class="
        flex h-24 items-center
        border-b border-white/10
        px-6
    ">
<div>

    <img
        src="{{ asset('images/oneshop/logo.png') }}"
        class="h-10 w-auto mb-2"
        alt="OneShop"
    >

    <p class="text-xs text-blue-200">
        Sistema de gestión 
    </p>

</div>
</div>

    <nav class="space-y-6 px-4 py-6">


        {{-- Principal --}}
        <div>

            <p class="
                mb-3
                px-3
                text-xs
                font-semibold
                uppercase
                tracking-wider
                text-blue-300
            ">
                Principal
            </p>


            <a
                href="{{ route('dashboard') }}"
                class="
                    flex items-center gap-3
                    rounded-xl
                    px-4 py-3
                    text-sm
                    font-medium
                    transition
                    {{ request()->routeIs('dashboard')
                        ? 'bg-white text-blue-950'
                        : 'text-blue-100 hover:bg-white/10'
                    }}
                "
            >

                <x-ui.icon
                    name="home"
                    size="20"
                />

                Inicio

            </a>


        </div>



        {{-- Operaciones --}}
        <div>

            <p class="
                mb-3
                px-3
                text-xs
                font-semibold
                uppercase
                tracking-wider
                text-blue-300
            ">
                Operaciones
            </p>



            @if(auth()->user()?->tienePermiso('inventario.ver'))

            <a
                href="{{ route('inventario.index') }}"
                class="
                    mb-2
                    flex items-center gap-3
                    rounded-xl
                    px-4 py-3
                    text-sm
                    font-medium
                    transition

                    {{ request()->routeIs('inventario.*')
                        ? 'bg-white text-blue-950'
                        : 'text-blue-100 hover:bg-white/10'
                    }}
                "
            >

                <x-ui.icon
                    name="package"
                    size="20"
                />

                Inventario

            </a>

            @endif



            @if(auth()->user()?->tienePermiso('importacion.ver'))

            <a
                href="{{ route('importaciones.index') }}"
                class="
                    flex items-center gap-3
                    rounded-xl
                    px-4 py-3
                    text-sm
                    font-medium
                    transition

                    {{ request()->routeIs('importaciones.*')
                        ? 'bg-white text-blue-950'
                        : 'text-blue-100 hover:bg-white/10'
                    }}
                "
            >

                <x-ui.icon
                    name="truck"
                    size="20"
                />

                Importaciones

            </a>

            @endif

            @if(auth()->user()?->tienePermiso('reservas.ver'))

            <a
                href="{{ route('reservas.index') }}"
                class="
                    mt-2 flex items-center gap-3 rounded-xl px-4 py-3
                    text-sm font-medium transition
                    {{ request()->routeIs('reservas.*')
                        ? 'bg-white text-blue-950'
                        : 'text-blue-100 hover:bg-white/10'
                    }}
                "
            >
                <x-ui.icon name="bookmark" size="20" />
                Reservas
            </a>

            @endif

            @if(auth()->user()?->tienePermiso('ventas.ver'))

            <a
                href="{{ route('ventas.index') }}"
                class="
                    mt-2 flex items-center gap-3 rounded-xl px-4 py-3
                    text-sm font-medium transition
                    {{ request()->routeIs('ventas.*')
                        ? 'bg-white text-blue-950'
                        : 'text-blue-100 hover:bg-white/10'
                    }}
                "
            >
                <x-ui.icon name="shopping-cart" size="20" />
                Ventas
            </a>

            @endif


        </div>

        @if(
    auth()->user()?->tienePermiso('importacion.ver')
)

    <a
        href="{{ route('envios-importacion.index') }}"
        class="
            flex
            items-center
            gap-3
            rounded-xl
            px-3
            py-2.5
            text-sm
            font-semibold
            transition

            {{
                request()->routeIs('envios-importacion.*')
                    ? 'bg-oneshop-light text-oneshop-primary'
                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            }}
        "
    >
        <x-ui.icon
            name="truck"
            size="19"
        />

        <span>
            Envíos a Oruro
        </span>
    </a>

@endif

        {{-- Próximos módulos --}}
        <div>

            <p class="
                mb-3
                px-3
                text-xs
                font-semibold
                uppercase
                tracking-wider
                text-blue-300
            ">
                Próximamente
            </p>


            <div class="
                flex items-center gap-3
                rounded-xl
                px-4 py-3
                text-sm
                text-blue-300
            ">

                <x-ui.icon name="shield"/>

                Garantías

            </div>


            <div class="
                flex items-center gap-3
                rounded-xl
                px-4 py-3
                text-sm
                text-blue-300
            ">

                <x-ui.icon name="chart"/>

                Reportes

            </div>


        </div>


    </nav>


</aside>
  
    {{-- Área principal --}}
    <div class="lg:pl-72">

        {{-- Topbar --}}
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="flex h-20 items-center justify-between px-4 sm:px-6 lg:px-8">

                <div class="flex items-center gap-4">
                    <button
                        @click="sidebarOpen = true"
                        class="rounded-lg border border-slate-200 p-2 lg:hidden"
                        type="button"
                    >
                        ☰
                    </button>

                    <div>
                        <p class="text-xs font-medium uppercase tracking-wider text-slate-400">
                            OneShop
                        </p>

                        <h2 class="font-semibold text-slate-900">
                            {{ $pageTitle ?? 'Panel de control' }}
                        </h2>
                    </div>
                </div>

                <div
                    x-data="{ open: false }"
                    class="relative"
                >
                    <button
                        @click="open = !open"
                        type="button"
                        class="flex items-center gap-3 rounded-xl px-3 py-2 hover:bg-slate-100"
                    >
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold">
                                {{ auth()->user()->name }}
                            </p>

                            <p class="text-xs text-slate-500">
                                {{ auth()->user()->email }}
                            </p>
                        </div>

                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-bold text-white">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                    </button>

                    <div
                        x-cloak
                        x-show="open"
                        @click.outside="open = false"
                        x-transition
                        class="absolute right-0 mt-2 w-52 rounded-xl border border-slate-200 bg-white p-2 shadow-xl"
                    >
                        <a
                            href="{{ route('profile.edit') }}"
                            class="block rounded-lg px-3 py-2 text-sm hover:bg-slate-100"
                        >
                            Mi perfil
                        </a>

                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                        >
                            @csrf

                            <button
                                type="submit"
                                class="block w-full rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                            >
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Contenido --}}
        <main class="p-4 sm:p-6 lg:p-8">
            {{ $slot }}
        </main>

    </div>
</div>

</body>
</html>
