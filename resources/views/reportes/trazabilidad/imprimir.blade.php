<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Imprimir trazabilidad | OneShop</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#e2e8f0;color:#0f172a;font-family:Arial,Helvetica,sans-serif;font-size:11px}
.print-toolbar{position:sticky;top:0;z-index:20;display:flex;justify-content:space-between;align-items:center;padding:12px 20px;background:#0f172a;color:white}
.print-toolbar button,.print-toolbar a{border:0;border-radius:8px;padding:9px 14px;font-weight:700;cursor:pointer;text-decoration:none}
.print-toolbar button{background:white;color:#0f172a}
.print-toolbar a{background:#334155;color:white}
.sheet{width:900px;max-width:calc(100% - 32px);margin:20px auto;padding:28px;background:white;box-shadow:0 8px 30px rgba(15,23,42,.12)}
.document-header{display:flex;justify-content:space-between;gap:20px;margin-bottom:18px;border-bottom:2px solid #0f172a;padding-bottom:12px}
.brand{font-size:11px;font-weight:800;letter-spacing:1.5px;color:#2563eb}
.document-title{margin-top:4px;font-size:25px;font-weight:800}
.document-subtitle{margin-top:3px;color:#64748b}
.generated{text-align:right;color:#475569;line-height:1.55}
table{border-collapse:collapse;width:100%}
.identity-table{margin-bottom:18px;table-layout:fixed}
.identity-table td{width:50%;border:1px solid #cbd5e1;padding:10px;vertical-align:top}
.label{display:block;text-transform:uppercase;font-size:8px;font-weight:800;color:#64748b;margin-bottom:4px}
.muted{color:#64748b;font-size:9px}
h2{margin:0 0 8px;font-size:15px}
.timeline-table{table-layout:fixed}
.timeline-table thead{display:table-header-group}
.timeline-table tr{page-break-inside:avoid}
.timeline-table th{background:#e2e8f0;color:#334155;border:1px solid #cbd5e1;padding:6px;text-transform:uppercase;font-size:8px;text-align:left}
.timeline-table td{border:1px solid #e2e8f0;padding:6px;vertical-align:top;word-break:break-word}
.empty{text-align:center;padding:24px!important;color:#64748b}
.historical-note{margin-top:14px;padding:10px;border:1px solid #bfdbfe;background:#eff6ff;color:#1e3a8a;line-height:1.45}
@page{size:A4 portrait;margin:10mm}
@media print{
    body{background:white;font-size:8px}
    .print-toolbar{display:none!important}
    .sheet{width:auto;max-width:none;margin:0;padding:0;box-shadow:none}
    .document-title{font-size:19px}
}
</style>
</head>
<body>
<div class="print-toolbar">
    <div><strong>Vista de impresión · Trazabilidad de equipos</strong></div>
    <div>
        <a href="{{ route('reportes.trazabilidad.detalle', $equipo) }}">Volver</a>
        <button type="button" onclick="window.print()">Imprimir expediente</button>
    </div>
</div>
<main class="sheet">@include('reportes.trazabilidad._documento-contenido')</main>
</body>
</html>
