<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Diagnostico extends Model
{
    protected $table = 'diagnosticos';

    protected $fillable = [
        'equipo_id',
        'revision_tecnica_id',
        'tecnico_id',
        'fecha_diagnostico',
        'nivel',
        'descripcion',
        'requiere_reparacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_diagnostico' => 'datetime',
            'requiere_reparacion' => 'boolean',
        ];
    }

    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(
            RevisionTecnica::class,
            'revision_tecnica_id'
        );
    }

    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'tecnico_id'
        );
    }

    public function reparaciones(): HasMany
    {
        return $this->hasMany(
            Reparacion::class,
            'diagnostico_id'
        );
    }
}