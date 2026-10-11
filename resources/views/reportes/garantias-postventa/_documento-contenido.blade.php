<div class="document-header">
    <div>
        <div class="brand">ONESHOP</div>
        <div class="document-title">Garantías y postventa</div>
        <div class="document-subtitle">Seguimiento de casos posteriores a la venta</div>
    </div>
    <div class="generated"><strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}<br><strong>Casos:</strong> {{ number_format($casos->count()) }}</div>
</div>
<table class="filters-table"><tr><td><strong>Periodo:</strong> {{ $filtrosHumanos['periodo'] }}</td><td><strong>Estado:</strong> {{ $filtrosHumanos['estado'] }}</td><td><strong>Cliente:</strong> {{ $filtrosHumanos['cliente'] }}</td><td><strong>Búsqueda:</strong> {{ $filtrosHumanos['busqueda'] }}</td></tr></table>
<table class="summary-table"><tr>
    <td><span class="summary-label">Casos activos</span><span class="summary-value">{{ number_format($resumen['activos']) }}</span></td>
    <td><span class="summary-label">Casos cerrados</span><span class="summary-value">{{ number_format($resumen['cerrados']) }}</span></td>
    <td><span class="summary-label">Con reemplazo</span><span class="summary-value">{{ number_format($resumen['con_reemplazo']) }}</span></td>
    <td><span class="summary-label">Tiempo promedio cierre</span><span class="summary-value">{{ number_format($resumen['tiempo_promedio_dias'], 1, ',', '.') }} días</span></td>
</tr></table>
<h2>Detalle de casos</h2>
<table class="detail-table">
<thead><tr><th>Caso</th><th>Cliente</th><th>Equipo</th><th>Estado</th><th>Apertura</th><th class="right">Días</th><th>Diagnóstico / resolución</th><th>Reemplazo / ajuste</th></tr></thead>
<tbody>
@forelse($casos as $caso)
<tr>
    <td>{{ $caso['numero'] }}</td>
    <td>{{ $caso['cliente'] }}</td>
    <td>{{ $caso['equipo'] }}<br><span class="muted">{{ $caso['producto'] }}</span></td>
    <td>{{ $caso['estado_humano'] }}</td>
    <td>{{ $caso['fecha_apertura']?->format('d/m/Y H:i') ?? '—' }}</td>
    <td class="right">{{ number_format($caso['dias_atencion'], 1, ',', '.') }}</td>
    <td>{{ $caso['diagnostico'] ?: 'Sin diagnóstico registrado' }}@if($caso['resolucion'])<br><strong>Resolución:</strong> {{ $caso['resolucion'] }}@endif</td>
    <td>@if($caso['tiene_reemplazo']){{ $caso['equipo_reemplazo'] ?: 'Reemplazo registrado' }}@if($caso['diferencia_ajuste'] !== null)<br><span class="muted">{{ $caso['moneda_ajuste'] ?: 'BOB' }} {{ number_format(abs($caso['diferencia_ajuste']), 2, ',', '.') }} · {{ str($caso['estado_ajuste'] ?? '')->replace('_', ' ')->title() }}</span>@endif @else Sin reemplazo @endif</td>
</tr>
@empty
<tr><td colspan="8" class="empty">No existen casos para los filtros seleccionados.</td></tr>
@endforelse
</tbody>
</table>
<div class="historical-note"><strong>Criterio del reporte:</strong> el tiempo de atención se calcula desde la apertura hasta el cierre; para casos activos se muestra el tiempo transcurrido hasta el momento de generación. Los ajustes económicos corresponden al snapshot registrado al autorizar el reemplazo.</div>
