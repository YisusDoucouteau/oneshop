@props([
    'title' => 'OneShop',
    'pageTitle' => 'Panel de control',
])

<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ $title ?? 'OneShop' }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

</head>


<body
    class="
        bg-slate-50
        text-slate-900
        antialiased
    "
>

<div
    x-data="{ sidebarOpen: false }"
    class="min-h-screen"
>

    {{-- Overlay móvil --}}

    <div
        x-cloak
        x-show="sidebarOpen"
        x-transition.opacity
        @click="sidebarOpen = false"

        class="
            fixed
            inset-0
            z-40
            bg-slate-950/30
            backdrop-blur-sm
            lg:hidden
        "
    ></div>


    {{-- ========================================================= --}}
    {{-- SIDEBAR --}}
    {{-- ========================================================= --}}

    <aside
        :class="
            sidebarOpen
                ? 'translate-x-0'
                : '-translate-x-full'
        "

        class="
            fixed
            inset-y-0
            left-0
            z-50

            flex
            w-72
            flex-col

            transform
border-b
border-[rgb(47,59,223)]
bg-[#D3E6FF]
from-oneshop-light
via-[#F2F7FF]
to-[#E1EEFF]

shadow-sm

            transition-transform
            duration-200

            lg:translate-x-0
        "
    >

        {{-- Marca --}}

        <div
            class="
                flex
                h-24
                shrink-0
                items-center

                border-b
                border-slate-200

                px-6
            "
        >

            <div>

                <img
                    src="{{ asset('images/oneshop/logo.png') }}"
                    alt="OneShop"

                    class="
                        mb-2
                        h-9
                        w-auto
                    "
                >

                <p
                    class="
                        text-xs
                        font-semibold
                        text-oneshop-dark
                    "
                >
                    Sistema de gestión
                </p>

            </div>

        </div>


        {{-- Navegación --}}

        <nav
            @click="
                if (
                    $event.target.closest('a')
                    && window.innerWidth < 1024
                ) {
                    sidebarOpen = false
                }
            "

            class="
                flex-1
                space-y-7
                overflow-y-auto
                px-4
                py-6
            "
        >

            {{-- Principal --}}

            <section>

                <p
                    class="
                        mb-2
                        px-3
                        text-[11px]
                        font-bold
                        uppercase
                        tracking-[0.14em]
                        text-slate-500
                    "
                >
                    Principal
                </p>

                <x-ui.menu-item
                    route="dashboard"
                    label="Inicio"
                    icon="home"
                />

            </section>


            {{-- Operaciones --}}

            <section>

                <p
                    class="
                        mb-2
                        px-3
                        text-[11px]
                        font-bold
                        uppercase
                        tracking-[0.14em]
                        text-slate-500
                    "
                >
                    Operaciones
                </p>

                <div class="space-y-1">

                    <x-ui.menu-item
                        route="importaciones.index"
                        label="Importaciones"
                        icon="truck"
                        permission="importacion.ver"
                    />

                    <x-ui.menu-item
                        route="envios-importacion.index"
                        label="Envíos a Oruro"
                        icon="truck"
                        permission="importacion.ver"
                    />

                    <x-ui.menu-item
                        route="inventario.index"
                        label="Inventario"
                        icon="package"
                        permission="inventario.ver"
                    />

                </div>

            </section>


            {{-- Comercial --}}

            <section>

                <p
                    class="
                        mb-2
                        px-3
                        text-[11px]
                        font-bold
                        uppercase
                        tracking-[0.14em]
                        text-slate-500
                    "
                >
                    Comercial
                </p>

                <div class="space-y-1">

                    <x-ui.menu-item
                        route="reservas.index"
                        label="Reservas"
                        icon="bookmark"
                        permission="reservas.ver"
                    />

                    <x-ui.menu-item
                        route="ventas.index"
                        label="Ventas"
                        icon="cart"
                        permission="ventas.ver"
                    />

                    <x-ui.menu-item
                        route="garantias.index"
                        label="Garantías"
                        icon="shield"
                        permission="garantias.ver"
                    />

                </div>

            </section>


            {{-- Próximamente --}}

            <section>

                <p
                    class="
                        mb-2
                        px-3
                        text-[11px]
                        font-bold
                        uppercase
                        tracking-[0.14em]
                        text-slate-500
                    "
                >
                    Próximamente
                </p>

                <div
                    class="
                        flex
                        items-center
                        gap-3
                        rounded-lg
                        border
                        border-dashed
                        border-slate-300
                        px-3
                        py-2.5
                        text-sm
                        font-semibold
                        text-slate-500
                    "
                >

                    <span
                        class="
                            flex
                            h-8
                            w-8
                            items-center
                            justify-center
                            text-slate-500
                        "
                    >
                        <x-ui.icon
                            name="chart"
                            size="19"
                        />
                    </span>

                    <span>
                        Reportes
                    </span>

                    <span
                        class="
                            ml-auto
                            rounded-md
                            bg-oneshop-light
                            px-2
                            py-1
                            text-[10px]
                            font-bold
                            uppercase
                            tracking-wide
                            text-oneshop-dark
                        "
                    >
                        Próximo
                    </span>

                </div>

            </section>

        </nav>

    </aside>


    {{-- ========================================================= --}}
    {{-- CONTENIDO PRINCIPAL --}}
    {{-- ========================================================= --}}

    <div
        class="
            min-h-screen
            min-w-0
            lg:pl-72
        "
    >

        {{-- Topbar --}}

        <header
            class="
                sticky
                top-0
                z-30
                border-b
                border-slate-200
                bg-white/95
                backdrop-blur
            "
        >

            <div
                class="
                    flex
                    h-20
                    items-center
                    justify-between
                    gap-4
                    px-4
                    sm:px-6
                    lg:px-8
                "
            >

                <div
                    class="
                        flex
                        min-w-0
                        items-center
                        gap-3
                    "
                >

                    <button
                        type="button"
                        @click="sidebarOpen = true"
                        aria-label="Abrir navegación"

                        class="
                            inline-flex
                            h-10
                            w-10
                            items-center
                            justify-center
                            rounded-lg
                            border
                            border-blue-200
                            bg-oneshop-light
                            text-oneshop-dark
                            transition
                            hover:bg-blue-100
                            lg:hidden
                        "
                    >

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            class="h-5 w-5"
                        >
                            <path d="M4 6h16" />
                            <path d="M4 12h16" />
                            <path d="M4 18h16" />
                        </svg>

                    </button>


                    <div class="min-w-0">

                        <p
                            class="
                                text-[11px]
                                font-bold
                                uppercase
                                tracking-[0.14em]
                                text-oneshop-dark
                            "
                        >
                            OneShop
                        </p>

                        <h1
                            class="
                                truncate
                                text-lg
                                font-bold
                                text-slate-900
                            "
                        >
                            {{ $pageTitle ?? 'Panel de control' }}
                        </h1>

                    </div>

                </div>


                {{-- Usuario --}}

                <div
                    x-data="{ open: false }"
                    class="relative shrink-0"
                >

                    <button
                        type="button"
                        @click="open = !open"
                        :aria-expanded="open"

                        class="
                            flex
                            items-center
                            gap-3
                            rounded-xl
                            px-2
                            py-2
                            transition
                            hover:bg-slate-50
                        "
                    >

                        <div
                            class="
                                hidden
                                max-w-56
                                text-right
                                sm:block
                            "
                        >

                            <p
                                class="
                                    truncate
                                    text-sm
                                    font-bold
                                    text-slate-900
                                "
                            >
                                {{ auth()->user()->name }}
                            </p>

                            <p
                                class="
                                    truncate
                                    text-xs
                                    text-slate-600
                                "
                            >
                                {{ auth()->user()->email }}
                            </p>

                        </div>


                        <div
                            class="
                                flex
                                h-10
                                w-10
                                items-center
                                justify-center
                                rounded-xl
                                border
                                border-blue-200
                                bg-oneshop-light
                                text-sm
                                font-bold
                                text-oneshop-dark
                            "
                        >
                            {{
                                strtoupper(
                                    substr(
                                        auth()->user()->name,
                                        0,
                                        1
                                    )
                                )
                            }}
                        </div>


                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            class="
                                hidden
                                h-4
                                w-4
                                text-slate-500
                                sm:block
                            "
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="m6 9 6 6 6-6"
                            />
                        </svg>

                    </button>


                    <div
                        x-cloak
                        x-show="open"
                        @click.outside="open = false"
                        x-transition.origin.top.right

                        class="
                            absolute
                            right-0
                            mt-2
                            w-56
                            overflow-hidden
                            rounded-xl
                            border
                            border-slate-200
                            bg-white
                            p-2
                            shadow-xl
                        "
                    >

                        <a
                            href="{{ route('profile.edit') }}"

                            class="
                                flex
                                items-center
                                gap-3
                                rounded-lg
                                px-3
                                py-2.5
                                text-sm
                                font-semibold
                                text-slate-800
                                transition
                                hover:bg-oneshop-soft
                                hover:text-oneshop-dark
                            "
                        >

                            <x-ui.icon
                                name="settings"
                                size="18"
                            />

                            Mi perfil

                        </a>


                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                        >

                            @csrf

                            <button
                                type="submit"

                                class="
                                    w-full
                                    rounded-lg
                                    px-3
                                    py-2.5
                                    text-left
                                    text-sm
                                    font-semibold
                                    text-red-700
                                    transition
                                    hover:bg-red-50
                                "
                            >
                                Cerrar sesión
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </header>


        <main
            class="
                min-w-0
                p-4
                sm:p-6
                lg:p-8
            "
        >
            {{ $slot }}
        </main>

       </div>

</div>


{{-- Scripts agregados por las vistas y partials --}}
@stack('scripts')


</body>

</html>