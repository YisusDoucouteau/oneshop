<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OneShop centraliza importaciones, inventario, ventas y trazabilidad de equipos tecnológicos.">
    <title>OneShop | Gestión y trazabilidad</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-white antialiased">
    <div class="relative isolate min-h-screen overflow-hidden">
        <div class="absolute inset-0 -z-20 bg-[radial-gradient(circle_at_top_left,_rgba(0,87,184,0.35),_transparent_42%),radial-gradient(circle_at_85%_25%,_rgba(14,165,233,0.18),_transparent_30%),linear-gradient(135deg,#020617_0%,#071b34_55%,#020617_100%)]"></div>
        <div class="absolute inset-0 -z-10 opacity-[0.08] [background-image:linear-gradient(rgba(255,255,255,.8)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,.8)_1px,transparent_1px)] [background-size:48px_48px]"></div>

        <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-6 sm:px-8 lg:px-10">
            <a href="{{ url('/') }}" class="flex items-center gap-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-400 focus:ring-offset-4 focus:ring-offset-slate-950" aria-label="Inicio de OneShop">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white shadow-xl shadow-blue-950/30">
                    <img src="{{ asset('images/oneshop/logo.png') }}" alt="" class="h-auto w-10">
                </span>
                <span>
                    <span class="block text-sm font-black tracking-[0.22em] text-white">ONESHOP</span>
                    <span class="block text-xs text-slate-400">Sistema de gestión</span>
                </span>
            </a>

            <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:border-sky-300/50 hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-sky-400">
                {{ auth()->check() ? 'Ir al panel' : 'Ingresar al sistema' }}
                <span aria-hidden="true">→</span>
            </a>
        </header>

        <main class="mx-auto grid w-full max-w-7xl items-center gap-14 px-5 pb-16 pt-10 sm:px-8 lg:grid-cols-[1.08fr_.92fr] lg:px-10 lg:pb-24 lg:pt-20">
            <section>
                <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-sky-400/20 bg-sky-400/10 px-4 py-2 text-xs font-bold uppercase tracking-[0.18em] text-sky-200">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_14px_rgba(52,211,153,.9)]"></span>
                    Control integral del negocio
                </div>

                <h1 class="max-w-3xl text-4xl font-black leading-[1.08] tracking-tight text-white sm:text-6xl lg:text-7xl">
                    Cada equipo,
                    <span class="bg-gradient-to-r from-sky-300 via-blue-400 to-indigo-400 bg-clip-text text-transparent">bajo control.</span>
                </h1>

                <p class="mt-7 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">
                    OneShop conecta importaciones, recepción, inventario, ventas y garantías en un solo flujo, con trazabilidad por unidad y datos confiables para decidir.
                </p>

                <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
                       class="inline-flex items-center justify-center gap-3 rounded-2xl bg-blue-600 px-6 py-4 text-sm font-extrabold text-white shadow-xl shadow-blue-950/40 transition hover:-translate-y-0.5 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-sky-300 focus:ring-offset-4 focus:ring-offset-slate-950">
                        {{ auth()->check() ? 'Abrir panel de control' : 'Acceder a OneShop' }}
                        <span aria-hidden="true">→</span>
                    </a>
                    <a href="#capacidades" class="inline-flex items-center justify-center rounded-2xl border border-white/15 bg-white/5 px-6 py-4 text-sm font-bold text-slate-200 transition hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-sky-300">
                        Conocer el sistema
                    </a>
                </div>

                <dl class="mt-12 grid max-w-2xl grid-cols-3 gap-3 border-t border-white/10 pt-7">
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Unidades</dt><dd class="mt-1 text-sm font-bold text-slate-200">Trazables</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Procesos</dt><dd class="mt-1 text-sm font-bold text-slate-200">Conectados</dd></div>
                    <div><dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Decisiones</dt><dd class="mt-1 text-sm font-bold text-slate-200">Con evidencia</dd></div>
                </dl>
            </section>

            <section id="capacidades" aria-label="Capacidades principales" class="relative">
                <div class="absolute -inset-8 -z-10 rounded-full bg-blue-500/10 blur-3xl"></div>
                <div class="rounded-[2rem] border border-white/10 bg-white/[0.07] p-5 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-7">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-300">Flujo operativo</p>
                            <h2 class="mt-1 text-xl font-extrabold">Del origen a la postventa</h2>
                        </div>
                        <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-center text-xs font-bold text-emerald-300">Trazabilidad activa</span>
                    </div>

                    <div class="space-y-3">
                        @foreach([
                            ['01', 'Importación y recepción', 'Compras, procedencia, costos separados y verificación física.', 'text-sky-300', 'bg-sky-400/10'],
                            ['02', 'Inventario por unidad', 'Seriales, configuración, sede, estado e historial completo.', 'text-blue-300', 'bg-blue-400/10'],
                            ['03', 'Ventas y garantías', 'Reservas, pagos, entrega y seguimiento postventa conectado.', 'text-indigo-300', 'bg-indigo-400/10'],
                            ['04', 'Reportes para decidir', 'Indicadores operativos, comerciales y de trazabilidad.', 'text-violet-300', 'bg-violet-400/10'],
                        ] as [$number, $title, $description, $tone, $background])
                            <article class="flex gap-4 rounded-2xl border border-white/10 bg-slate-950/35 p-4 transition hover:border-sky-300/25 hover:bg-white/[0.08]">
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $background }} text-sm font-black {{ $tone }}">{{ $number }}</span>
                                <div><h3 class="font-extrabold text-white">{{ $title }}</h3><p class="mt-1 text-sm leading-6 text-slate-400">{{ $description }}</p></div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        </main>

        <footer class="mx-auto flex w-full max-w-7xl flex-col gap-2 border-t border-white/10 px-5 py-6 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
            <p>OneShop · Gestión de inventarios y ventas</p>
            <p>Trazabilidad por unidad desde la compra hasta la postventa</p>
        </footer>
    </div>
</body>
</html>
