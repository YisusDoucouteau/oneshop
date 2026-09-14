<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Equipo extends Model
{
    protected $table = 'equipos';

    protected $fillable = [
        'producto_id',
        'detalle_lote_id',
        'almacen_actual_id',
        'estado_actual_id',
        'condicion_fisica_id',
        'codigo_interno',
        'serial_fabricante',
        'fecha_registro',
        'fecha_disponible',
        'observacion',
        'activo',
    ];

    protected $casts = [
            'fecha_registro' => 'datetime',
            'fecha_disponible' => 'datetime',
            'activo' => 'boolean',
        ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function detalleLote(): BelongsTo
    {
        return $this->belongsTo(
            DetalleLote::class,
            'detalle_lote_id'
        );
    }

    public function almacenActual(): BelongsTo
    {
        return $this->belongsTo(
            Almacen::class,
            'almacen_actual_id'
        );
    }

    public function estadoActual(): BelongsTo
    {
        return $this->belongsTo(
            EstadoEquipo::class,
            'estado_actual_id'
        );
    }

    public function condicionFisica(): BelongsTo
    {
        return $this->belongsTo(
            CondicionFisica::class,
            'condicion_fisica_id'
        );
    }

    public function especificacion(): HasOne
    {
        return $this->hasOne(
            EspecificacionEquipo::class,
            'equipo_id'
        );
    }

    public function historialEstados(): HasMany
    {
        return $this->hasMany(
            HistorialEstadoEquipo::class,
            'equipo_id'
        )->orderBy('fecha_cambio');
    }
    public function asignacionesComponentes(): HasMany
{
    return $this->hasMany(
        AsignacionComponente::class,
        'equipo_id'
    );
}

public function transferencias(): BelongsToMany
{
    return $this->belongsToMany(
        Transferencia::class,
        'transferencias_equipos',
        'equipo_id',
        'transferencia_id'
    );
}
public function revisionesTecnicas(): HasMany
{
    return $this->hasMany(
        RevisionTecnica::class,
        'equipo_id'
    );
}

public function diagnosticos(): HasMany
{
    return $this->hasMany(
        Diagnostico::class,
        'equipo_id'
    );
}

public function reparaciones(): HasMany
{
    return $this->hasMany(
        Reparacion::class,
        'equipo_id'
    );
}
public function detallesReservas(): HasMany
{
    return $this->hasMany(
        DetalleReserva::class,
        'equipo_id'
    );
}

public function detallesVentas(): HasMany
{
    return $this->hasMany(
        DetalleVenta::class,
        'equipo_id'
    );
}
public function precios(): HasMany
{
    return $this->hasMany(
        PrecioEquipo::class,
        'equipo_id'
    );
}
public function precioVigente(): HasOne
{
    return $this->hasOne(
        PrecioEquipo::class,
        'equipo_id'
    )
        ->where('vigente', true)
        ->latestOfMany('vigente_desde');
}
public function adquisicionDirecta(): HasOne
{
    return $this->hasOne(
        AdquisicionDirecta::class,
        'equipo_id'
    );
}

public function costos(): HasMany
{
    return $this->hasMany(
        CostoEquipo::class,
        'equipo_id'
    );
}
public function casosGarantia(): HasMany
{
    return $this->hasMany(
        CasoGarantia::class,
        'equipo_afectado_id'
    );
}
public function detallesGarantia(): HasMany
{
    return $this->hasMany(
        DetalleVenta::class,
        'equipo_id'
    );
}
public function incorporacionUnidad(): HasOne
{
    return $this->hasOne(
        IncorporacionUnidadAdquirida::class,
        'equipo_id'
    );
}

}