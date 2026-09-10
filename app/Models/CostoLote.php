<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostoLote extends Model
{
    protected $table = 'costos_lotes';

    protected $fillable = [

        'lote_id',
        'tipo_costo_id',
        'moneda_id',
        'tipo_cambio_id',
        'monto_origen',
        'monto_bob',
        'fecha_costo',
        'referencia',
        'registrado_por_id',
        'observacion',
        'estado',
        'anulado_por_id',
        'fecha_anulacion',
        'motivo_anulacion',

    ];

    protected $casts = [
        'monto_origen' => 'decimal:2',
        'monto_bob' => 'decimal:2',
        'fecha_costo' => 'date',
        'fecha_anulacion'=>'datetime',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
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

    public function asignaciones(): HasMany
    {
        return $this->hasMany(
            AsignacionCostoLote::class,
            'costo_lote_id'
        );
    }
    public function asignacionesUnidades(): HasMany
    {
        return $this->hasMany(
            AsignacionCostoUnidadAdquirida::class,
            'costo_lote_id'
        );
    }
}
