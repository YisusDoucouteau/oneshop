<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Etiqueta {{ $equipo->codigo_interno }} | OneShop</title>
    <style>
        @page { size: 100mm 70mm; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; color: #0f172a; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px; background: #0f172a; color: #fff; }
        .toolbar a, .toolbar button { border: 0; border-radius: 8px; padding: 9px 13px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .toolbar a { background: #334155; color: #fff; }
        .toolbar button { background: #fff; color: #0f172a; }
        .stage { display: flex; min-height: calc(100vh - 58px); align-items: flex-start; justify-content: center; padding: 24px; }
        .label { position: relative; width: 100mm; height: 70mm; overflow: hidden; background: #fff; padding: 5mm 6mm 4mm 8mm; box-shadow: 0 0 0 .25mm #cbd5e1; }
        .corner { position: absolute; top: 4mm; left: 4mm; width: 27mm; height: 20mm; border-left: 1.2mm solid #0755b8; border-top: 1.2mm solid #0755b8; }
        .head { position: relative; z-index: 1; display: flex; min-height: 10mm; align-items: flex-start; justify-content: flex-end; }
        .logo-frame { width: 35mm; height: 8mm; overflow: hidden; }
        .logo { display: block; width: 35mm; height: auto; transform: translateY(-6.7mm); }
        .fields { position: relative; z-index: 1; margin-top: 1mm; }
        .field { display: grid; grid-template-columns: 30mm 1fr; align-items: center; min-height: 7mm; }
        .field span { padding-right: 2mm; color: #0755b8; font-size: 7.5pt; font-weight: 900; letter-spacing: -.1pt; text-transform: uppercase; }
        .field strong { min-width: 0; overflow: hidden; border: .35mm solid #2563d8; padding: 1mm 2mm .8mm; text-align: center; text-overflow: ellipsis; white-space: nowrap; color: #050505; font-size: 9.5pt; line-height: 1; }
        .identity { display: grid; grid-template-columns: 1fr 1.35fr; gap: 3mm; margin-top: 2mm; }
        .identity-item { display: flex; min-width: 0; align-items: baseline; gap: 1.5mm; border-top: .25mm solid #bfdbfe; padding-top: 1.2mm; }
        .identity-item span { flex: none; color: #0755b8; font-size: 5.5pt; font-weight: 900; text-transform: uppercase; }
        .identity-item strong { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 6.5pt; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .stage { min-height: 0; padding: 0; }
            .label { border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('inventario.show', $equipo) }}">Volver al equipo</a>
        <button type="button" onclick="window.print()">Imprimir etiqueta</button>
    </div>

    <main class="stage">
        <section class="label" aria-label="Etiqueta imprimible del equipo">
            <div class="corner" aria-hidden="true"></div>
            <header class="head">
                <div class="logo-frame"><img class="logo" src="{{ asset('images/oneshop/logo.png') }}" alt="OneShop"></div>
            </header>

            <div class="fields">
                <div class="field"><span>Marca / modelo:</span><strong>{{ trim(($equipo->producto?->marca?->nombre ?? '') . ' ' . ($equipo->producto?->modelo ?? '')) ?: '—' }}</strong></div>
                <div class="field"><span>Procesador:</span><strong>{{ $equipo->especificacion?->procesador ?: '—' }} {{ $equipo->especificacion?->generacion_procesador ?: '' }}</strong></div>
                <div class="field"><span>Almacenamiento:</span><strong>{{ $equipo->especificacion?->almacenamiento_gb ? $equipo->especificacion->almacenamiento_gb . ' GB ' . $equipo->especificacion->tipo_almacenamiento : '—' }}</strong></div>
                <div class="field"><span>Memoria RAM:</span><strong>{{ $equipo->especificacion?->ram_gb ? $equipo->especificacion->ram_gb . ' GB' : '—' }}</strong></div>
                <div class="field"><span>Pantalla:</span><strong>{{ $equipo->especificacion?->pantalla_pulgadas ? $equipo->especificacion->pantalla_pulgadas . ' pulgadas' : '—' }}</strong></div>
                <div class="field"><span>Video:</span><strong>{{ $equipo->especificacion?->tarjeta_grafica ?: '—' }}</strong></div>
                <div class="identity">
                    <div class="identity-item"><span>Código:</span><strong>{{ $equipo->codigo_interno }}</strong></div>
                    <div class="identity-item"><span>S/N:</span><strong>{{ $equipo->serial_fabricante ?: '—' }}</strong></div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
