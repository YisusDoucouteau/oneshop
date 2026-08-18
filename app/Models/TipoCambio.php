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
        'fecha',
        'fuente',
        'usuario_id',
        'observacion',
    ];

    protected $casts = [
            'valor' => 'decimal:6',
            'fecha' => 'datetime',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_id'
        );
    }
}