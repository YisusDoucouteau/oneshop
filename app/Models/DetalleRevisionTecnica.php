<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleRevisionTecnica extends Model
{
    protected $table = 'detalles_revisiones_tecnicas';

    protected $fillable = [
        'revision_tecnica_id',
        'item_checklist_id',
        'valor_booleano',
        'valor_numerico',
        'valor_texto',
        'valor_opcion',
        'cumple',
        'observacion',
    ];

    protected $casts = [
            'valor_booleano' => 'boolean',
            'valor_numerico' => 'decimal:2',
            'cumple' => 'boolean',
        ];

    public function revision(): BelongsTo
    {
        return $this->belongsTo(
            RevisionTecnica::class,
            'revision_tecnica_id'
        );
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(
            ItemChecklist::class,
            'item_checklist_id'
        );
    }
}