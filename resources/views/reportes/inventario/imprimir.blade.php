<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imprimir reporte de inventario | OneShop</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e2e8f0; color: #0f172a; font-family: Arial, Helvetica, sans-serif; font-size: 12px; }
        .print-toolbar { position: sticky; top: 0; z-index: 20; display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 12px 20px; background: #0f172a; color: white; }
        .print-toolbar button, .print-toolbar a { border: 0; border-radius: 8px; padding: 9px 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .print-toolbar button { background: white; color: #0f172a; }
        .print-toolbar a { background: #334155; color: white; }
        .sheet { width: 1120px; max-width: calc(100% - 32px); margin: 20px auto; padding: 28px; background: white; box-shadow: 0 8px 30px rgba(15, 23, 42, .12); }
        .document-header { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 18px; border-bottom: 2px solid #0f172a; padding-bottom: 12px; }
        .brand { font-size: 11px; font-weight: 800; letter-spacing: 1.5px; color: #2563eb; }
        .document-title { margin-top: 4px; font-size: 25px; font-weight: 800; }
        .document-subtitle { margin-top: 3px; color: #64748b; }
        .generated { text-align: right; color: #475569; line-height: 1.55; }
        table { border-collapse: collapse; width: 100%; }
        .filters-table { margin-bottom: 14px; }
        .filters-table td { border: 1px solid #cbd5e1; padding: 8px; color: #334155; }
        .summary-table { margin-bottom: 18px; table-layout: fixed; }
        .summary-table td { border: 1px solid #cbd5e1; padding: 10px; background: #f8fafc; }
        .summary-label { display: block; text-transform: uppercase; font-size: 9px; font-weight: 800; color: #64748b; margin-bottom: 4px; }
        .summary-value { display: block; font-size: 15px; font-weight: 800; }
        h2 { margin: 0 0 8px; font-size: 15px; }
        .detail-table thead { display: table-header-group; }
        .detail-table tr { page-break-inside: avoid; }
        .detail-table th { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; padding: 7px; text-transform: uppercase; font-size: 9px; text-align: left; }
        .detail-table td { border: 1px solid #e2e8f0; padding: 7px; vertical-align: top; }
        .right { text-align: right !important; }
        .empty { text-align: center; padding: 24px !important; color: #64748b; }
        .historical-note { margin-top: 14px; padding: 10px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; line-height: 1.45; }
        small { color: #64748b; }
        @page { size: A4 landscape; margin: 12mm; }
        @media print {
            body { background: white; font-size: 9px; }
            .print-toolbar { display: none !important; }
            .sheet { width: auto; max-width: none; margin: 0; padding: 0; box-shadow: none; }
            .document-title { font-size: 20px; }
            .summary-value { font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div><strong>Vista de impresión · Reporte de inventario</strong></div>
        <div>
            <a href="{{ route('reportes.inventario.index', request()->except('page')) }}">Volver</a>
            <button type="button" onclick="window.print()">Imprimir reporte</button>
        </div>
    </div>

    <main class="sheet">
        @include('reportes.inventario._documento-contenido')
    </main>
</body>
</html>
