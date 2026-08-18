<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemChecklist extends Model
{
    protected $table = 'items_checklist';

    protected $fillable = [
        'plantilla_checklist_id',
        'codigo',
        'nombre',
        'descripcion',
        'tipo_respuesta',
        'unidad',
        'valor_minimo',
        'valor_maximo',
        'opciones',
        'requerido',
        'orden',
        'activo',
    ];

    protected $casts = [
            'valor_minimo' => 'decimal:2',
            'valor_maximo' => 'decimal:2',
            'opciones' => 'array',
            'requerido' => 'boolean',
            'orden' => 'integer',
            'activo' => 'boolean',
        ];

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(
            PlantillaChecklist::class,
            'plantilla_checklist_id'
        );
    }

    public function detallesRevisiones(): HasMany
    {
        return $this->hasMany(
            DetalleRevisionTecnica::class,
            'item_checklist_id'
        );
    }
}