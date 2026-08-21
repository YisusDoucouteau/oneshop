<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TipoCambio extends Model
{
    protected $table = 'tipos_cambio';

    protected $fillable = [
        'moneda_origen_id',
        'moneda_destino_id',
        'valor',
        'fecha_vigencia',
        'fuente',
        'registrado_por_id',
        'observacion',
    ];

    protected $casts = [
        'valor' => 'decimal:6',
        'fecha_vigencia' => 'datetime',
    ];

    public function monedaOrigen(): BelongsTo
    {
        return $this->belongsTo(
            Moneda::class,
            'moneda_origen_id'
        );
    }

    public function monedaDestino(): BelongsTo
    {
        return $this->belongsTo(
            Moneda::class,
            'moneda_destino_id'
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