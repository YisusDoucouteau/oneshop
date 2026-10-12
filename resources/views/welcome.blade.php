<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OneShop centraliza importaciones, inventario, ventas y trazabilidad de equipos tecnológicos.">
    <title>OneShop | Gestión y trazabilidad</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-slate-950 antialiased">
    <header class="border-b border-slate-200">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-5 lg:px-10">
            <a href="{{ url('/') }}" class="flex items-center gap-3" aria-label="Inicio de OneShop">
                <img src="{{ asset('images/oneshop/logo.png') }}" alt="OneShop" class="h-9 w-9 object-contain">
                <span class="text-sm font-extrabold tracking-[0.18em]">ONESHOP</span>
            </a>

            <a
                href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                class="text-sm font-bold text-slate-700 underline decoration-slate-300 underline-offset-4 transition hover:text-blue-700"
            >
                {{ auth()->check() ? 'Abrir sistema' : 'Ingresar al sistema' }}
            </a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-7xl px-6 lg:px-10">
        <section class="grid gap-14 py-16 lg:min-h-[calc(100vh-150px)] lg:grid-cols-[1.15fr_.85fr] lg:items-center lg:py-24">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-blue-700">Gestión de inventarios y ventas</p>
                <h1 class="mt-6 max-w-4xl text-5xl font-extrabold leading-[1.04] tracking-[-0.045em] sm:text-6xl lg:text-7xl">
                    Control claro para cada equipo.
                </h1>
                <p class="mt-8 max-w-2xl text-lg leading-8 text-slate-600">
                    Importación, recepción, inventario, venta y garantía conectados mediante una trazabilidad individual y verificable.
                </p>

                <a
                    href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                    class="mt-10 inline-flex items-center gap-4 border-b-2 border-blue-700 pb-2 text-sm font-extrabold text-blue-700 transition hover:border-slate-950 hover:text-slate-950"
                >
                    {{ auth()->check() ? 'Continuar al panel' : 'Acceder a OneShop' }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="border-l border-slate-200 pl-8 sm:pl-12" aria-label="Flujo del sistema">
                <p class="mb-7 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">Flujo del sistema</p>
                <ol class="space-y-7">
                    @foreach([
                        ['01', 'Importaciones', 'Compra y procedencia'],
                        ['02', 'Inventario', 'Recepción y control por unidad'],
                        ['03', 'Ventas', 'Reserva, pago y entrega'],
                        ['04', 'Postventa', 'Garantía e historial'],
                    ] as [$number, $title, $description])
                        <li class="grid grid-cols-[34px_1fr] gap-4">
                            <span class="pt-0.5 text-xs font-bold text-blue-700">{{ $number }}</span>
                            <div>
                                <h2 class="font-bold">{{ $title }}</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200">
        <div class="mx-auto flex w-full max-w-7xl flex-col gap-1 px-6 py-5 text-xs text-slate-500 sm:flex-row sm:justify-between lg:px-10">
            <p>OneShop</p>
            <p>Trazabilidad desde la compra hasta la postventa</p>
        </div>
    </footer>
</body>
</html>
