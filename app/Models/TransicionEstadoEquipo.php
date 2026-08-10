<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransicionEstadoEquipo extends Model
{
    protected $table = 'transiciones_estados_equipos';

    protected $fillable = [
        'estado_origen_id',
        'estado_destino_id',
        'requiere_autorizacion',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_autorizacion' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function estadoOrigen(): BelongsTo
    {
        return $this->belongsTo(
            EstadoEquipo::class,
            'estado_origen_id'
        );
    }

    public function estadoDestino(): BelongsTo
    {
        return $this->belongsTo(
            EstadoEquipo::class,
            'estado_destino_id'
        );
    }
}