<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantillaChecklist extends Model
{
    protected $table = 'plantillas_checklist';

    protected $fillable = [
        'categoria_producto_id',
        'codigo',
        'nombre',
        'version',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(
            CategoriaProducto::class,
            'categoria_producto_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            ItemChecklist::class,
            'plantilla_checklist_id'
        )->orderBy('orden');
    }

    public function revisiones(): HasMany
    {
        return $this->hasMany(
            RevisionTecnica::class,
            'plantilla_checklist_id'
        );
    }
}