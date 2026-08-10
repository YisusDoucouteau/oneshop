<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RevisionTecnica extends Model
{
    protected $table = 'revisiones_tecnicas';

    protected $fillable = [
        'equipo_id',
        'plantilla_checklist_id',
        'tecnico_id',
        'tipo_revision',
        'estado',
        'resultado_general',
        'fecha_inicio',
        'fecha_fin',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'datetime',
            'fecha_fin' => 'datetime',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function plantilla(): BelongsTo
    {
        return $this->belongsTo(
            PlantillaChecklist::class,
            'plantilla_checklist_id'
        );
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'tecnico_id'
        );
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(
            DetalleRevisionTecnica::class,
            'revision_tecnica_id'
        );
    }

    public function diagnostico(): HasOne
    {
        return $this->hasOne(
            Diagnostico::class,
            'revision_tecnica_id'
        );
    }
}