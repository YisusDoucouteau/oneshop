<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoCosto extends Model
{
    protected $table = 'tipos_costos';

    protected $fillable = [
        'codigo',
        'nombre',
        'ambito',
        'descripcion',
        'activo',
    ];

    protected $casts = [
            'activo' => 'boolean',
        ];

    public function costosLotes(): HasMany
    {
        return $this->hasMany(
            CostoLote::class,
            'tipo_costo_id'
        );
    }

    public function costosEquipos(): HasMany
    {
        return $this->hasMany(
            CostoEquipo::class,
            'tipo_costo_id'
        );
    }
}