<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'proveedor_id',
        'codigo',
        'referencia_compra',
        'fecha_compra',
        'origen',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleLote::class,
            'lote_id'
        );
    }

    /**
     * Unidades físicas originadas por cualquiera de las líneas del lote.
     *
     * Esta relación permite medir la recepción real en Cochabamba sin
     * reutilizar detalles_lotes.cantidad_recibida, campo reservado para la
     * etapa posterior de recepción/incorporación en Oruro.
     */
    public function unidadesAdquiridas(): HasManyThrough
    {
        return $this->hasManyThrough(
            UnidadAdquirida::class,
            DetalleLote::class,
            'lote_id',
            'detalle_lote_id',
            'id',
            'id'
        );
    }

    public function eventosLogisticos(): HasMany
    {
        return $this->hasMany(
            EventoLogisticoLote::class,
            'lote_id'
        )->orderBy('fecha_evento');
    }

    public function costos(): HasMany
    {
        return $this->hasMany(
            CostoLote::class,
            'lote_id'
        );
    }
    public function getRouteKeyName(): string
    {
        return 'codigo';
    }
}