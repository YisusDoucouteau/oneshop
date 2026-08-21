<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoEventoLogistico extends Model
{
    protected $table = 'tipos_eventos_logisticos';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'activo',
    ];

    protected $casts = [
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function eventos(): HasMany
    {
        return $this->hasMany(
            EventoLogisticoLote::class,
            'tipo_evento_logistico_id'
        );
    }
}