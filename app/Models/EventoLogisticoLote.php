<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventoLogisticoLote extends Model
{
    protected $table = 'eventos_logisticos_lotes';

    protected $fillable = [
        'lote_id',
        'tipo_evento_logistico_id',
        'usuario_id',
        'fecha_evento',
        'ubicacion',
        'descripcion',
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
    ];

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }

    public function tipoEvento(): BelongsTo
    {
        return $this->belongsTo(
            TipoEventoLogistico::class,
            'tipo_evento_logistico_id'
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