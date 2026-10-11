<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas | OneShop</title>
    <style>
        @page { margin: 22px 24px 34px 24px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 8.5px; margin: 0; }
        .document-header { display: table; width: 100%; margin-bottom: 13px; border-bottom: 2px solid #0f172a; padding-bottom: 10px; }
        .document-header > div { display: table-cell; vertical-align: top; }
        .brand { font-size: 7.5px; font-weight: bold; letter-spacing: 1.35px; color: #2563eb; }
        .document-title { margin-top: 3px; font-size: 19px; font-weight: bold; }
        .document-subtitle { margin-top: 2px; font-size: 8.5px; color: #64748b; }
        .generated { text-align: right; font-size: 7.5px; line-height: 1.55; color: #475569; }
        table { border-collapse: collapse; width: 100%; }
        .meta-title { margin-bottom: 4px; text-transform: uppercase; letter-spacing: .8px; font-size: 6.8px; font-weight: bold; color: #94a3b8; }
        .filters-table { margin-bottom: 9px; }
        .filters-table td { border: 1px solid #dbe3ed; padding: 6px 7px; color: #475569; background: #fbfdff; }
        .filters-table strong { color: #0f172a; }
        .hero-summary { margin-bottom: 14px; table-layout: fixed; }
        .hero-summary td { border: 1px solid #dbe3ed; padding: 8px; vertical-align: top; background: #f8fafc; }
        .hero-summary .hero-main { width: 28%; background: #0f172a; color: #fff; border-color: #0f172a; }
        .hero-label, .summary-label { display: block; text-transform: uppercase; font-size: 6.5px; font-weight: bold; letter-spacing: .5px; color: #64748b; margin-bottom: 3px; }
        .hero-main .hero-label { color: #93c5fd; }
        .hero-value { display: block; font-size: 15px; font-weight: bold; }
        .hero-caption { display: block; margin-top: 3px; font-size: 7px; color: #cbd5e1; }
        .summary-value { display: block; font-size: 10px; font-weight: bold; color: #0f172a; }
        .summary-value.accent, .accent-text { color: #047857; font-weight: bold; }
        .section-heading { display: table; width: 100%; margin: 0 0 6px 0; }
        .section-heading > span { display: table-cell; font-size: 10px; font-weight: bold; }
        .section-heading .section-count { text-align: right; font-size: 7px; font-weight: normal; color: #64748b; }
        .detail-table { table-layout: fixed; }
        .detail-table thead { display: table-header-group; }
        .detail-table tr { page-break-inside: avoid; }
        .detail-table th { background: #e8eef5; color: #334155; border: 1px solid #d6dee8; padding: 5px; text-transform: uppercase; font-size: 6.6px; text-align: left; letter-spacing: .2px; }
        .detail-table td { border: 1px solid #e2e8f0; padding: 5px; vertical-align: top; word-wrap: break-word; }
        .right { text-align: right !important; }
        .muted { color: #64748b; }
        .empty { text-align: center; padding: 18px !important; color: #64748b; }
        .historical-note { margin-top: 10px; padding: 7px 9px; border-left: 3px solid #2563eb; background: #eff6ff; color: #1e3a8a; font-size: 7.5px; line-height: 1.5; }
        .footer { position: fixed; left: 0; right: 0; bottom: -22px; height: 18px; border-top: 1px solid #dbe3ed; padding-top: 5px; color: #94a3b8; font-size: 6.5px; text-align: center; }
        .page-number:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">OneShop · Información gerencial · Reporte de ventas · Página <span class="page-number"></span></div>
    @include('reportes.ventas._documento-contenido')
</body>
</html>
