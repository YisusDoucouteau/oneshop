<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostoEquipo extends Model
{
    protected $table = 'costos_equipos';

    protected $fillable = [
        'equipo_id',
        'tipo_costo_id',
        'moneda_id',
        'tipo_cambio_id',
        'monto_origen',
        'monto_bob',
        'fecha_costo',
        'referencia',
        'registrado_por_id',
        'descripcion',
    ];

    protected $casts = [
        'monto_origen' => 'decimal:2',
        'monto_bob' => 'decimal:2',
        'fecha_costo' => 'date',
    ];

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function tipoCosto(): BelongsTo
    {
        return $this->belongsTo(
            TipoCosto::class,
            'tipo_costo_id'
        );
    }

    public function moneda(): BelongsTo
    {
        return $this->belongsTo(Moneda::class);
    }

    public function tipoCambio(): BelongsTo
    {
        return $this->belongsTo(
            TipoCambio::class,
            'tipo_cambio_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por_id'
        );
    }
}