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
        .label { width: 100mm; height: 70mm; overflow: hidden; border: .4mm solid #111827; background: #f59e42; padding: 5mm; }
        .head { display: flex; align-items: center; justify-content: space-between; border-bottom: .35mm solid #111827; padding-bottom: 2.5mm; }
        .brand { font-size: 12pt; font-weight: 900; letter-spacing: 1.5pt; }
        .kind { font-size: 6.5pt; font-weight: 800; letter-spacing: .8pt; text-transform: uppercase; }
        .code { font-size: 8pt; font-weight: 800; }
        .fields { margin-top: 3mm; }
        .field { display: grid; grid-template-columns: 28mm 1fr; align-items: end; min-height: 7mm; border-bottom: .25mm solid rgba(17,24,39,.75); }
        .field span { padding-bottom: 1.2mm; font-size: 7pt; font-weight: 800; letter-spacing: .35pt; text-transform: uppercase; }
        .field strong { min-width: 0; overflow: hidden; padding: 0 0 1.1mm 2mm; text-overflow: ellipsis; white-space: nowrap; font-size: 10pt; }
        .last-row { display: grid; grid-template-columns: 1fr 40mm; gap: 4mm; }
        .last-row .field { grid-template-columns: 17mm 1fr; }
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
            <header class="head">
                <div class="brand">ONESHOP</div>
                <div class="code">CÓDIGO: {{ $equipo->codigo_interno }}</div>
            </header>

            <div class="fields">
                <div class="field"><span>Marca / modelo:</span><strong>{{ trim(($equipo->producto?->marca?->nombre ?? '') . ' ' . ($equipo->producto?->modelo ?? '')) ?: '—' }}</strong></div>
                <div class="field"><span>Procesador:</span><strong>{{ $equipo->especificacion?->procesador ?: '—' }} {{ $equipo->especificacion?->generacion_procesador ?: '' }}</strong></div>
                <div class="field"><span>Almacenamiento:</span><strong>{{ $equipo->especificacion?->almacenamiento_gb ? $equipo->especificacion->almacenamiento_gb . ' GB ' . $equipo->especificacion->tipo_almacenamiento : '—' }}</strong></div>
                <div class="field"><span>Memoria RAM:</span><strong>{{ $equipo->especificacion?->ram_gb ? $equipo->especificacion->ram_gb . ' GB' : '—' }}</strong></div>
                <div class="field"><span>Pantalla:</span><strong>{{ $equipo->especificacion?->pantalla_pulgadas ? $equipo->especificacion->pantalla_pulgadas . ' pulgadas' : '—' }}</strong></div>
                <div class="last-row">
                    <div class="field"><span>Video:</span><strong>{{ $equipo->especificacion?->tarjeta_grafica ?: '—' }}</strong></div>
                    <div class="field"><span>S/N:</span><strong>{{ $equipo->serial_fabricante ?: '—' }}</strong></div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
