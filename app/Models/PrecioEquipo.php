<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class PrecioEquipo extends Model
{
    protected $table = 'precios_equipos';

    protected $fillable = [
        'equipo_id',
        'tipo_cambio_id',
        'costo_total_snapshot',
        'precio_sugerido',
        'precio_publico',
        'precio_minimo_autorizado',
        'vigente_desde',
        'vigente_hasta',
        'vigente',
        'usuario_aprobacion_id',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'costo_total_snapshot' => 'decimal:2',
            'precio_sugerido' => 'decimal:2',
            'precio_publico' => 'decimal:2',
            'precio_minimo_autorizado' => 'decimal:2',
            'vigente_desde' => 'datetime',
            'vigente_hasta' => 'datetime',
            'vigente' => 'boolean',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipoCambio(): BelongsTo
    {
        return $this->belongsTo(
            TipoCambio::class,
            'tipo_cambio_id'
        );
    }

    public function aprobadoPor(): BelongsTo
{
    return $this->belongsTo(
        User::class,
        'aprobado_por_id'
    );
}
    public function solicitudesDescuentos(): HasMany
{
    return $this->hasMany(
        SolicitudDescuento::class,
        'precio_equipo_id'
    );
}
}