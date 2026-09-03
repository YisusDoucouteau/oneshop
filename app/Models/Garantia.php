<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class Garantia extends Model
{
    protected $table = 'garantias';


    protected $fillable = [

        'numero',

        'detalle_venta_id',

        'politica_garantia_id',

        'fecha_inicio',

        'fecha_fin',

        'fecha_limite_cambio_inicial',

        'duracion_meses_snapshot',

        'condiciones_snapshot',

        'exclusiones_snapshot',

        'estado',

    ];


    protected $casts = [

        'fecha_inicio' => 'datetime',

        'fecha_fin' => 'datetime',

        'fecha_limite_cambio_inicial' => 'datetime',

    ];


    public function detalleVenta(): BelongsTo
    {
        return $this->belongsTo(
            DetalleVenta::class
        );
    }


    public function politicaGarantia(): BelongsTo
    {
        return $this->belongsTo(
            PoliticaGarantia::class
        );
    }


    public function estaVigente(): bool
    {
        return $this->estado === 'VIGENTE'
            && $this->fecha_fin->isFuture();
    }
    public function casosGarantia(): HasMany
{
    return $this->hasMany(
        CasoGarantia::class,
        'garantia_id'
    );
}
}