<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de inventario | OneShop</title>
    <style>
        @page { margin: 22px 24px 34px 24px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 9px; margin: 0; }
        .document-header { display: table; width: 100%; margin-bottom: 14px; border-bottom: 2px solid #0f172a; padding-bottom: 10px; }
        .document-header > div { display: table-cell; vertical-align: top; }
        .brand { font-size: 9px; font-weight: bold; letter-spacing: 1.5px; color: #2563eb; }
        .document-title { margin-top: 3px; font-size: 20px; font-weight: bold; }
        .document-subtitle { margin-top: 2px; font-size: 9px; color: #64748b; }
        .generated { text-align: right; font-size: 8px; line-height: 1.55; color: #475569; }
        table { border-collapse: collapse; width: 100%; }
        .filters-table { margin-bottom: 10px; }
        .filters-table td { border: 1px solid #cbd5e1; padding: 6px 7px; color: #334155; }
        .filters-table strong { color: #0f172a; }
        .summary-table { margin-bottom: 14px; table-layout: fixed; }
        .summary-table td { border: 1px solid #cbd5e1; padding: 7px; background: #f8fafc; }
        .summary-label { display: block; text-transform: uppercase; font-size: 7px; font-weight: bold; color: #64748b; margin-bottom: 3px; }
        .summary-value { display: block; font-size: 11px; font-weight: bold; color: #0f172a; }
        h2 { font-size: 11px; margin: 0 0 6px 0; }
        .detail-table { table-layout: fixed; }
        .detail-table thead { display: table-header-group; }
        .detail-table tr { page-break-inside: avoid; }
        .detail-table th { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; padding: 5px; text-transform: uppercase; font-size: 7px; text-align: left; }
        .detail-table td { border: 1px solid #e2e8f0; padding: 5px; vertical-align: top; word-wrap: break-word; }
        .right { text-align: right !important; }
        .empty { text-align: center; padding: 18px !important; color: #64748b; }
        .historical-note { margin-top: 10px; padding: 7px 9px; border: 1px solid #bfdbfe; background: #eff6ff; color: #1e3a8a; font-size: 8px; line-height: 1.45; }
        small { color: #64748b; }
        .footer { position: fixed; left: 0; right: 0; bottom: -22px; height: 18px; border-top: 1px solid #cbd5e1; padding-top: 5px; color: #64748b; font-size: 7px; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        OneShop · Reporte de inventario · Página <span class="page-number"></span>
    </div>

    @include('reportes.inventario._documento-contenido')
</body>
</html>
