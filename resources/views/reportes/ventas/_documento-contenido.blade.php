@php
    $etiquetaEstado = fn ($estado) => match ((string) $estado) {
        'REGISTRADA' => 'Registrada',
        'ANULADA' => 'Anulada',
        default => ucwords(mb_strtolower(str_replace('_', ' ', (string) $estado), 'UTF-8')),
    };

    $codigoDocumento = 'REP-VTA-' . now()->format('Ymd-His');
@endphp

<div class="document-header">
    <div>
        <div class="brand">ONESHOP · INFORMACIÓN GERENCIAL</div>
        <div class="document-title">Reporte de ventas</div>
        <div class="document-subtitle">Facturación, costo histórico y rentabilidad del periodo</div>
    </div>
    <div class="generated">
        <strong>{{ $codigoDocumento }}</strong><br>
        Generado {{ now()->format('d/m/Y H:i') }}<br>
        {{ auth()->user()?->name ?? 'Sistema' }}
    </div>
</div>

<div class="meta-title">Filtros aplicados</div>
<table class="filters-table">
    <tr>
        <td><strong>Periodo</strong><br>{{ $filtrosHumanos['periodo'] }}</td>
        <td><strong>Vendedor</strong><br>{{ $filtrosHumanos['vendedor'] }}</td>
        <td><strong>Cliente</strong><br>{{ $filtrosHumanos['cliente'] }}</td>
        <td><strong>Estado</strong><br>{{ $filtrosHumanos['estado'] }}</td>
    </tr>
    @if($filtrosHumanos['busqueda'] !== 'Sin búsqueda adicional')
        <tr>
            <td colspan="4"><strong>Búsqueda:</strong> {{ $filtrosHumanos['busqueda'] }}</td>
        </tr>
    @endif
</table>

<table class="hero-summary">
    <tr>
        <td class="hero-main">
            <span class="hero-label">Total vendido</span>
            <span class="hero-value">Bs {{ number_format($resumen['total_vendido'], 2, ',', '.') }}</span>
            <span class="hero-caption">{{ number_format($resumen['cantidad_ventas']) }} {{ $resumen['cantidad_ventas'] === 1 ? 'operación registrada' : 'operaciones registradas' }}</span>
        </td>
        <td>
            <span class="summary-label">Costo histórico</span>
            <span class="summary-value">Bs {{ number_format($resumen['costo_total'], 2, ',', '.') }}</span>
        </td>
        <td>
            <span class="summary-label">Utilidad</span>
            <span class="summary-value accent">Bs {{ number_format($resumen['utilidad_total'], 2, ',', '.') }}</span>
        </td>
        <td>
            <span class="summary-label">Margen</span>
            <span class="summary-value">{{ number_format($resumen['margen_promedio'], 2, ',', '.') }}%</span>
        </td>
        <td>
            <span class="summary-label">Ticket promedio</span>
            <span class="summary-value">Bs {{ number_format($resumen['ticket_promedio'], 2, ',', '.') }}</span>
        </td>
    </tr>
</table>

<div class="section-heading">
    <span>Detalle de operaciones</span>
    <span class="section-count">{{ number_format($resumen['cantidad_ventas']) }} registros</span>
</div>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 15%;">Venta</th>
            <th style="width: 10%;">Fecha</th>
            <th style="width: 15%;">Cliente</th>
            <th style="width: 13%;">Vendedor</th>
            <th style="width: 10%;" class="right">Total</th>
            <th style="width: 10%;" class="right">Costo</th>
            <th style="width: 10%;" class="right">Utilidad</th>
            <th style="width: 8%;" class="right">Margen</th>
            <th style="width: 9%;">Estado</th>
        </tr>
    </thead>
    <tbody>
        @forelse($ventas as $venta)
            @php
                $costoVenta = (float) $venta->detalles->sum(
                    fn ($detalle) => (float) $detalle->costo_unitario_snapshot * (int) $detalle->cantidad
                );
                $utilidadVenta = (float) $venta->total - $costoVenta;
                $margenVenta = (float) $venta->total > 0
                    ? ($utilidadVenta / (float) $venta->total) * 100
                    : 0;
                $cliente = $venta->cliente_nombre_snapshot
                    ?: $venta->cliente?->nombre_completo
                    ?: 'Cliente no identificado';
            @endphp
            <tr>
                <td>{{ $venta->numero }}</td>
                <td>{{ $venta->fecha_venta?->format('d/m/Y H:i') }}</td>
                <td>{{ $cliente }}</td>
                <td>{{ $venta->vendedor?->name ?? 'No disponible' }}</td>
                <td class="right">Bs {{ number_format((float) $venta->total, 2, ',', '.') }}</td>
                <td class="right muted">Bs {{ number_format($costoVenta, 2, ',', '.') }}</td>
                <td class="right accent-text">Bs {{ number_format($utilidadVenta, 2, ',', '.') }}</td>
                <td class="right">{{ number_format($margenVenta, 2, ',', '.') }}%</td>
                <td>{{ $etiquetaEstado($venta->estado) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="empty">No existen ventas para los filtros seleccionados.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="historical-note">
    <strong>Criterio histórico.</strong>
    El costo y la utilidad se obtienen de los snapshots congelados al registrar cada venta. El informe no reescribe operaciones pasadas con precios o tipos de cambio posteriores.
</div>
