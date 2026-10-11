<div class="document-header">
    <div>
        <div class="brand">ONESHOP</div>
        <div class="document-title">Trazabilidad del equipo</div>
        <div class="document-subtitle">Expediente cronológico individual</div>
    </div>
    <div class="generated">
        <strong>Generado:</strong> {{ now()->format('d/m/Y H:i') }}<br>
        <strong>Equipo:</strong> {{ $equipo->codigo_interno ?: 'EQ-' . $equipo->id }}<br>
        <strong>Eventos:</strong> {{ number_format($eventos->count()) }}
    </div>
</div>

<table class="identity-table">
    <tr>
        <td><span class="label">Código interno</span><strong>{{ $equipo->codigo_interno ?: '—' }}</strong></td>
        <td><span class="label">Serial fabricante</span><strong>{{ $equipo->serial_fabricante ?: '—' }}</strong></td>
    </tr>
    <tr>
        <td><span class="label">Producto</span><strong>{{ $equipo->producto?->nombre ?: 'No registrado' }}</strong><br><span class="muted">{{ collect([$equipo->producto?->marca?->nombre, $equipo->producto?->modelo])->filter()->implode(' · ') ?: '—' }}</span></td>
        <td><span class="label">Estado / ubicación</span><strong>{{ $equipo->estadoActual?->nombre ?: 'Sin estado' }}</strong><br><span class="muted">{{ $equipo->almacenActual?->nombre ?: 'Ubicación no registrada' }}</span></td>
    </tr>
    <tr>
        <td><span class="label">Procedencia</span><strong>{{ $lote?->codigo ?: 'Sin lote' }}</strong><br><span class="muted">{{ $proveedor ?: 'Proveedor no registrado' }}</span></td>
        <td><span class="label">Código de trazabilidad</span><strong>{{ $codigoTrazabilidad ?: 'No aplica' }}</strong><br><span class="muted">{{ $equipo->condicionFisica?->nombre ?: 'Condición física no registrada' }}</span></td>
    </tr>
</table>

<h2>Historia cronológica</h2>
<table class="timeline-table">
    <thead>
        <tr>
            <th style="width:15%">Fecha</th>
            <th style="width:15%">Etapa</th>
            <th style="width:20%">Evento</th>
            <th style="width:32%">Detalle</th>
            <th style="width:18%">Responsable / observación</th>
        </tr>
    </thead>
    <tbody>
    @forelse($eventos as $evento)
        <tr>
            <td>{{ $evento['fecha']->format('d/m/Y H:i') }}</td>
            <td>{{ str($evento['tipo'])->replace('_', ' ')->title() }}</td>
            <td><strong>{{ $evento['titulo'] }}</strong></td>
            <td>{{ $evento['detalle'] ?: '—' }}</td>
            <td>
                {{ $evento['usuario'] ?: '—' }}
                @if($evento['observacion'])
                    <br><span class="muted">{{ $evento['observacion'] }}</span>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty">No existen eventos de trazabilidad registrados para este equipo.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="historical-note">
    <strong>Criterio del informe:</strong> la trazabilidad consolida los eventos registrados por los módulos operativos de OneShop y los ordena cronológicamente. El documento no modifica ni recrea información histórica; refleja los registros disponibles al momento de su generación.
</div>
