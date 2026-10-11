<div class="document-header">
    <div>
        <div class="brand">ONESHOP</div>
        <div class="document-title">Reporte de inventario</div>
        <div class="document-subtitle">Existencias serializadas formalizadas en el sistema</div>
    </div>
    <div class="generated">
        <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}<br>
        <strong>Usuario:</strong> {{ auth()->user()?->name ?? 'Sistema' }}
    </div>
</div>

<table class="filters-table">
    <tr>
        <td><strong>Almacén</strong><br>{{ $filtrosHumanos['almacen'] }}</td>
        <td><strong>Estado</strong><br>{{ $filtrosHumanos['estado'] }}</td>
        <td><strong>Búsqueda</strong><br>{{ $filtrosHumanos['busqueda'] }}</td>
    </tr>
</table>

<table class="summary-table">
    <tr>
        <td>
            <span class="summary-label">Equipos</span>
            <span class="summary-value">{{ number_format($resumen['total']) }}</span>
        </td>
        <td>
            <span class="summary-label">Disponibles</span>
            <span class="summary-value">{{ number_format($resumen['disponibles']) }}</span>
        </td>
        <td>
            <span class="summary-label">Reservados</span>
            <span class="summary-value">{{ number_format($resumen['reservados']) }}</span>
        </td>
        <td>
            <span class="summary-label">Vendidos</span>
            <span class="summary-value">{{ number_format($resumen['vendidos']) }}</span>
        </td>
        <td>
            <span class="summary-label">Con precio vigente</span>
            <span class="summary-value">{{ number_format($resumen['con_precio']) }}</span>
        </td>
    </tr>
</table>

<h2>Detalle de equipos</h2>

<table class="detail-table">
    <thead>
        <tr>
            <th style="width: 12%;">Código</th>
            <th style="width: 13%;">Serial</th>
            <th style="width: 25%;">Producto</th>
            <th style="width: 16%;">Almacén</th>
            <th style="width: 13%;">Estado</th>
            <th style="width: 11%;" class="right">Precio</th>
            <th style="width: 10%;">Registro</th>
        </tr>
    </thead>
    <tbody>
        @forelse($equipos as $equipo)
            <tr>
                <td>{{ $equipo->codigo_interno }}</td>
                <td>{{ $equipo->serial_fabricante ?: '—' }}</td>
                <td>
                    {{ $equipo->producto?->nombre ?? 'Sin producto' }}
                    @if($equipo->producto?->marca?->nombre || $equipo->producto?->modelo)
                        <br><small>
                            {{ $equipo->producto?->marca?->nombre ?? '' }}
                            @if($equipo->producto?->modelo) · {{ $equipo->producto->modelo }} @endif
                        </small>
                    @endif
                </td>
                <td>{{ $equipo->almacenActual?->nombre ?? 'Sin almacén' }}</td>
                <td>{{ $equipo->estadoActual?->nombre ?? 'Sin estado' }}</td>
                <td class="right">
                    @if($equipo->precioVigente)
                        Bs {{ number_format((float) $equipo->precioVigente->precio_publico, 2, ',', '.') }}
                    @else
                        Sin precio
                    @endif
                </td>
                <td>{{ $equipo->fecha_registro?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="empty">No existen equipos para los filtros seleccionados.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="historical-note">
    <strong>Criterio del reporte:</strong>
    se muestran equipos activos formalizados en el inventario. El estado, almacén y precio corresponden a su situación vigente al momento de generar el documento.
</div>
