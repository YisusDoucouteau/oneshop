<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lote extends Model
{
    protected $table = 'lotes';

    protected $fillable = [
        'proveedor_id',
        'codigo',
        'referencia_compra',
        'origen',
        'estado',
        'observacion',
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
}