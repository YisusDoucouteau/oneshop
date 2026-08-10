<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    protected $table = 'almacenes';

    protected $fillable = [
        'codigo',
        'nombre',
        'ciudad',
        'direccion',
        'principal',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'principal' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function equipos(): HasMany
    {
        return $this->hasMany(
            Equipo::class,
            'almacen_actual_id'
        );
    }
}